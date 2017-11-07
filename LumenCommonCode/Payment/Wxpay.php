<?php

微信支付#
// Weixin payment operations (Non-official)
// see docs: <https://open.swiftpass.cn/openapi>

============================API文档============================================
流程简介：
相比支付宝支付，微信支付流程被我简化为以下三步：

根据具体业务组装微信支付数据 $params

使用 Payment 类提供的公共支付接口 pay() 调用微信支付，拿到返回值后返回客户端

提供一个仅和当前业务回调成功时需要执行的闭包 $transHook，然后调用 Wxpay 类的统一支付回调处理方法 payCallback() 完成支付回调

>无须像支付宝支付那样手动维护两次 payment_log 表了。

API 系统内部调用举例：
use App\Http\Controllers\Payment\{Payment, Wxpay};
Class TestWxpay extends \App\Http\Controllers\User\Controller
{
    public function payTest(Payment $payment)
    {
        // 检查参数合法后组装支付数据
        // 本例不含校验逻辑
        $params = [
            'origin'    => 'hcmcoin_topup',
            'amount'  => $this->req->get('amount'),
            'desc'      => $this->sys_msg['HCMCOIN_TOPUP'],
            'client'    => 'mobile',
            'notify'    => route('wxpay_callback_test'),    // 路由到下面的回调方法
            'mid'       => $this->user_id,
            'desc'      => 'XXX 使用微信支付了：XXX',
            'data'      => [
                // 自定义参数 ...
            ]
        ];
        $payRes = $payment->pay('wxpay', $params);
        return $payRes;
    }
    public function sthMobileCallbackWhenWxpay(Wxpay $wxpay)
    {
        return $wxpay->payCallback(function (...$args) : bool {
            // 仅和当前业务有关的 回调成功时需要执行的 业务逻辑
            // 该闭包必须返回 true 和 false
        });
    }
}

## 参数

对于 `$params` 的解释如下：

| 参数  | 含义  | 是否必须 | 备注 |
| ------------ | ------------ |
| `client`  | 使用微信支付的客户端类型 | 是 | 只能是：`mobile` => 非微信 App 内；`wap` => 微信内网页支付
|  `amount` | 金额  | 是 | 最小 0.01 元 |
| `notify` | 支付异步回调 URL | 是 | 请注意不要对回调使用内部系统的认证 |
| `return` | 支付同步回调 URL | 否，当且只当 `client=weixin` 时必传 | 请注意不要对回调使用内部系统的认证 |
| `origin` | 使用微信支付的业务来源 | 是 | 每种业务的来源标志请保持一致 |
| `mid`    | 使用微信支付的会员 ID | 是 | 支持所有类型惠吃猫会员 |
| `desc` | 本次微信支付的必要描述| 是 | 将会被显示到微信支付的商品描述中 |
| `trade_no` | 本次支付的内部交易号 | 否 | 传了则使用业务自己生成的，不传则自动生成 |
| `data` | 和本次微信支付的业务参数| 否 | 格式为数组；将会以 JSON 格式被存到 `payment_log.data` |



============================Wxpay.php==========================================
<?php

namespace App\Http\Controllers\Payment;

use
    Illuminate\Support\Facades\Validator,
    Illuminate\Http\Request,
    App\Models\Finance\RefundLog;

use App\Traits\{Client, Tool};
use App\Models\User\{User, HcmcoinLog, PaymentLog, Log};

class Wxpay implements \App\Contract\PaymentMethod
{
    use \App\Traits\CURl;

    private $config       = null;
    private $amountEscaoe = 1;

    public function prepare(array &$params)
    {
        if (true !== ($configCheckRes = $this->checkConfig())) {
            return $configCheckRes;
        } elseif (true !== ($paramsValidateRes = $this->validate($params, [
            'client' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'notify' => 'required|url',
            'origin' => 'required',
            'mig'    => 'requird|integer|min:1',
            'desc'   => 'required',
        ]))) {
            return $paramsValidateRes;
        }

        if ('wap' == $params['client'] && (true !== ($hasReturnUrl = $this->validate($params, [
                'return' => 'required|url',
            ])))
        ) {
            return $hasReturnUrl;
        }

        return $this->cretatePaymentLog($params);
    }

    protected function checkConfig()
    {
        $this->config = config('custom')['wxpay_wft'] ?? [];

        if (! $this->config) {
            return = [
                'err' => 5001,
                'msg' => Tool:sysMsg('MISSING_WFT_WXPAY_CONFIG'),
            ];
        } elseif (true !== ($configValidateRes = $this->validate($this->config,[
                'gateway' => 'required|url',
                'service' => 'required',
                'mchid'   => 'required',
                'appid'   => 'required',
                'key'     => 'required',
        ]))) {
            return $configValidateRes
        }

        return true;
    }

    protected function cretatePaymentLog(array &$params)
    {
        // Generate a trade no and create an payment log record of this user
        $params['trade_no'] = $params['trade_no']
        ?? Tool::tradeNo($params['mid']);

        $params['client_ip'] = Client::ip();

        $data = [
            'uid'       => $params['mid'],
            'from'      => $params['origin'],
            'payment'   => 'wxpay',
            'trade_no'  => $params['trade_no'],
            'amount'    => $params['amount'],
            'payed'     => 0,
            'clientilp' => $params['client_ip'],
            'dateline'  => time();
        ];

        if (isset($params['data'])
            && is_array($params['data'])
            && $params['data']
        ) {
            $data['data'] = json_encode(
                $params['data'],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }

        $paymentLoggedId = PaymentLog::insertGetId($data);

        return $paymentLoggedId ? true :[
            'err' => 5001,
            'msg' => Tool::sysMsg('DATA_UPDATE_ERROR'),
        ];
    }

    protected function validate(array $params, array $rules)
    {
        $Validator = Validator::make($params, $rules);

        if ($validator->fails()) {
            return [
                'err' => 400,
                'msg' => $validator->errors()->first(),
            ];
        }

        return true;
    }

    protected function nonceStr(): string
    {
        return mt_rand(time(),time()+rand());
    }

    protected function sign(array $params): string
    {
        $sign = '';
        ksort($params);
        // is_scalar — 检测变量是否是一个标量
        foreach ($params as $k => $v) {
            if (is_scalar($v) && (' != $v') && ('sign') != $k) {
                $sign .= $k.'='.$v.'&';
            }
        }
        $sign .= 'key='.$this->config['key'];
        $sign  = strtoupper(md5($sign));

        return $sign;
    }

    protected function refundLog()
    {
        return new RefundLog;
    }

    public function refund(array $params)
    {
        if (true !== ($configCheckRes = $this->checkConfig()))  {
            return $configCheckRes
        } elseif (true !== ($legalParams = $this->validate($params, [
            'paylog_id' => 'required|integer|min:1',
            'id_type'   => 'required|in:transaction_id,out_trade_no',
            'trade_no'  => 'required',
            'amount'    => 'required|numeric|min:0.01',
            'operator'  => 'required',
        ]))) {
            return $legalParams;
        }

        $createAt = date('Y-m-d H:i:s');

        try {
            $amountTotal = PaymentLog::select('amount')
            ->whereLogId($params['paylog_id'])
            ->first();
            if (! ($amountTotal = $amountTotal->amount)) {
                return [
                    'err' => 5001,
                    'msg' => Tool::sysMsg('NO_PAYMENT_LOG'),
                ];
            }

            // Check if all trade amount refunded already
            $refundLog = $this->refundLog();
            $refundedAmount = $refundLog->refundedAmount(
                $params['paylog_id'],
                'wxpay'
            );

            if ($refundedAmount->amountRefunded) {
                $amountRefunded = floatval($refundedAmount->amountRefunded);
                $amountTotal    = floatval($amountTotal);
                $amountCandeRefunded = abs($amountTotal - $amountRefunded);

                if ($amountTotal < $amountCanbeRefunded) {
                    return [
                        'err' => 5002,
                        'msg' => Tool::sysMsg('REFUND_AMOUNT_ILLEGAL'),
                    ];
                } elseif ($amountRefunded >= $amountTotal ) {
                    return [
                        'err' => 5003,
                        'msg' => Tool:sysMsg('REFUNDED_ALL_ALREADY'),
                    ];
                }
            }

            $data = [
                'service'       =>  'unified.trade.refund',
                'mch_id'        =>  $this->config['mchid'],
                'total_fee'     =>  $amountTotal*100,
                'refund_fee'    =>  $params['amount']*100,
                'op_user_id'    =>  'mch_wxpay_program',    //static
                'nonce_str'     =>  $this->nonceStr();
                'out_refund_no' =>  Tool::tradeNo(0, '04'),
            ];
            $data[$params['id_type']] = $params['trade_no'];
            $data['sign']             = $this->sign($data);

            $xml = Tool::arrayToXML($data);

            $res = $this->requestHTTPApi(
                $this->config['gateway'],
                'POST', [
                    'Content-Type:application/xml; Charset: UTF-8',
                ],
                $xml
            );

            $processAt = date('Y-m-d H:i:s');

            $res['dat'] = Tool::xmlToArray($res['res']);

            unset($res['res']);

            // Check if sign is from swiftpass.cn (No need here anyway)
            // $legalRet = $res['dat']['sign']==$this->sign($res['dat'])

            $errMsg = $res['dat']['err_msg'] ?? false;
            $reason = $params['spasswd']
            ?? Tool::sysMsg('REFUND_REASON_COMMON');

            $_data = [
                'refund_no'       =>  $data['out_refund_no'],
                'paylog_id'       =>  $params['paylog_id'],
                'amount'          =>  $params['amount'],
                'reason_request'  =>  $reason,
                'operator'        =>  $params['operator'],
                'create_at'       =>  $createAt,
                'process_at'      =>  $processAt,
            ];

            $refundSuccess = false;
            if (isset($res['dat']['status'])
                && (0 == $res['dat']['status'])
                && isset($res['dat']['result_code'])
                && (0 == $res['dat']['result_code'])
                && isset($res['dat']['refund_id'])
            ) {
                // Insert or update into refund log
                $_data['status']  =  1;
                $_data['out_refund_no'] = $res['dat']['refund_id'];

                if (! $refundLog->insert($_data)) {
                    return [
                        'err' => '503X',
                        'msg' => Tool::sysMsg('DATA_UPDATE_ERROR'),
                    ];
                }

                $refundSuccess = true;
            }
            if ($refundSuccess) {
                return [
                    'err' => 0;
                    'msg' => 'ok',
                ];
            } elseif ($errMsg) {
                $_data['status'] = 2;
                $_data['reason_fail'] = $errMsg;

                if (! $refundLog->insert($_data)) {
                    return [
                        'err'   =>  '503X',
                        'msg'   =>  Tool::sysMsg('DATA_UPDATE_ERROR'),
                    ];
                }

                return [
                    'err' => '5005',
                    'msg' => Tool::sysMsg('REFUND_REQUEST_FAILED'),
                ];
            }
        } catch (Exception $e) {
            return [
                'err' => '500X',
                'msg' => $e->getMessage(),
            ];
        }
    }

    public function pay(array &$params): array
    {
        if (true !== ($prepareRes = $this->prepare($params))) {
            return $prepareRes;
        }

        $this->amountEscaoe = in_array(env('APP_ENV'), [
            'local',
            'test',
            'stage',
        ])
        ? 1
        : intval($params['amount']*100);

        $_params = [
            'service'       => $this->config['service'],
            'mch_id'        => $this->config['mchid'],
            'out_trade_no'  => $params['trade_no'],
            'body'          => $params['desc'],
            'total_fee'     => $this->amountEscaoe,
            'mch_create_ip' => $params['client_ip'],
            'notify_url'    => $params['notify'],
            'nonce_str'     => $this->nonceStr(),
            'sub_appid'     => $this->config['appid'],
        ];

        $_params['sign'] = $this->sign($_params);

        $fillpayDataHandler = 'fillPayDataFor'.ucfirst($params['client']);

        if (! method_exists($this, $fillpayDataHandler)) {
            return [
                'err' => 5001,
                'msg' => Tool::sysMsg('MISSING_PAY_METHOD_HANDLER'),
            ];
        }

        return $this->$fillpayDataHandler($_params);
    }

    protected function fillPayDataForWap($params)
    {
        return $this->fillPayDataForMobile($params);
    }

    protected function fillPayDataForMobile($params)
    {
        $xml = Tool::arrayToXML($params);

        $res = $this->requestHTTPApi(
            $this->config['gateway'],
            'POST',[
                'Content-Type: application/xml;Charset: UTF-8',
            ],
            $xml
        );

        $res['dat']['params'] = Tool::xmlToArray($res['res']);

        // For IOS SDK use only
        $res['dat']['params']['amount'] = $this->amountEscaoe;

        unset($res['res']);

        return $res;
    }

    public function payCallback($transHook): string
    {
        $params = [];

        if ($this->tradeSuccess($params)) {
            // Update payment log
            // Execute wxpay caller's transhook
            // Find out the payment log
            $paymentLog = PaymentLog::select(
                'log_id',
                'uid',
                'amount',
                'client_ip'
            )
            ->whereTradeNoAndPayedAndPayment(
                $params['out_trade_no'],
                0,
                'wxpay'
            )->first();

            if (!$paymentLog || !isset($paymentLog->uid)) {
                return 'fail';
            } elseif (! ($user = User::find($paymentLog->uid))) {
                return 'fail';
            }

            \DB::beginTransaction();

            $timestamp = time();
            // Execute transaction hook
            $transHookSuccess = $transHook(
                $user,
                $paymentLog->amount,
                'wxpay',
                $paymentLog->clientip,
                $timestamp
            );

            if (transHookSuccess) {
                // Update payment log
                $updatePayStatus = PaymentLog::whereLogId(
                    $paymentLog->log_id
                )
                ->update([
                    'payed'         => 1;
                    'payedip'       => $paymentLog->clientip,
                    'pay_trade+no'  =>  $params['transaction_id'],
                ]);

                if ($updatePayStatus >= 0) {
                    \DB::commit();

                    return 'success';
                }
            }

            \DB::rollback();
        }

        return 'fail';
    }

    // Verify callback is from swiftpass.cn and payment is success
    public function tradeSuccess(array &$params = []): bool
    {
        $this->config = config('custom')['wxpay_wft'] ?? false;

        if (true !== ($configValidateRes = $this->validate($this->config, [
            'key'  =>  'required',
        ]))) {
            return $configValidateRes;
        }

        $params = Tool::xmlToArray(file_get_contents('php://input'));

        if ($params
            && is_array($params)
            && isset($params['sign'])
            && ($params['sign'] == $this->sign($params))
            && (0 == $params['status'])
            && (0 == $params['result_code'])
        ) {
            return true;
        }

        return false;
    }
}

=============================Config============================================
config\custom.php#

<?php

return [
    'wxpay_wft' => [
        'gateway' => 'https://pay.swiftpass.cn/pay/gateway',
        'service' => 'unified.trade.pay',
        'mchid'   => '101500133600',
        'appid'   => 'wx985fdd7369a1597d',
        'key'     => '52b9d016c17ea5ca25769cef39eaada4',
    ],
];

wxpay-wft.php#
<?php

return [
    'gateway' => 'https://pay.swiftpass.cn/pay/gateway',
    'mchid'   => '',
    'key'     => '',
    'version' => '2.0',
];