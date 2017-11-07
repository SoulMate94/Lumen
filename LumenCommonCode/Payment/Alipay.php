<?php

支付宝支付#

============================API文档============================================
API 内部系统调用举例：
<?php

use
    Illuminate\Http\Request,
    App\Http\Controllers\Payment\Payment,
    App\Http\Controllers\Payment\Alipay,
    App\Traits\Tool;

class TestAlipay
{
    // 支付
    public function pay(Payment $payment)
    {
        $user_id = 1;
        // 组装支付参数
        $params = [
            'client'      => 'web',
            'amount'   => 0.01,
            'notify'     => route('test_alipay_callback'),
            'desc'       => '惠吃猫-猫豆充值',
            'trade_no' => Tool::tradeNo($user_id),
        ];
        $payRes = $payment->pay('alipay', $params);
        return $payRes;
    }
    // 异步回调（需要为所有客户端均提供）
    // !!! 这里返回值必须是 `success` 或 `fail` 字符串
    public function notifyCallback(Request $req, Alipay $alipay)
    {
        $client = 'web';    // 和调用支付宝支付时的 client 类型对应
        // 校验回调合法性和支付状态
        if ($alipay->tradeSuccess($client)) {
            // 获取 out_trade_no
            $tradeNo = $req->get('out_trade_no');
            // 根据 out_trade_no 执行业务相关的更新逻辑 ...
            // $updated = true; 
            return $updated ? 'success' : 'fail';
        }
        return 'fail';
    }
    // 同步回调（只当 Web 端使用支付宝支付时需要）
    // !!! 不要在同步回调中进行更新数据库等操作
    public function returnCallback(Alipay $alipay)
    {
        // 校验回调合法性和支付状态
        if ($alipay->tradeSuccess('web')) {
            // 支付成功提示信息
            // return 'Paied.';
        }
        return 'Payment Failed.';
}

- 业务参数

数组`$params` 包含了调用支付宝支付的所需参数。详情如下：

| 名称 | 含义 | 是否必须 | 备注 |
| ------------ |
| `client`  | 客户端类型 | 是 | 只能是：`web` => PC Web端；`wap` => 手机 Web端；`mobile` => 手机 App 端  |
|  `amount` | 支付金额  | 是  | 人民币；可含2位小数
|  `notify` | 异步回调地址 | 是  | 注意不要在回调方法中使用自身系统的用户认证
|  `return` | 同步回调地址 | 否，只有 web/wap 要传递  | 注意不要在回调方法中使用自身系统的用户认证
|  `trade_no` | 内部交易流水号  | 是  | 需要调用在之前具体的业务中生成，对应支付宝回调参数中的 `out_trade_no`
|  `desc` | 支付对象的必要描述 | 是  | 对应支付宝订单的商品名称
| `body` | 支付对象的详细描述 | 否 | 对应支付宝订单的商品详情
| `qrpay` | Web 端支付时是否启用二维码支付 | 否| 值只能为：`0`／`1`／`2`／`3`／`4`


返回值:
成功情况：
{
  "err": 0,
  "msg": "ok",
  "dat": {
    "params": "_input_charset=utf-8&notify_url=http%3A%2F%2Ft0.api.hcmchi.com%2Fcbk%2Fhcmcoin%2Ftopup%2Falipay%2Fmobile&out_trade_no=20170901113027000100776567633091&partner=2088721066506713&payment_type=1&seller_id=hcmchi_zhifubao%40163.com&service=mobile.securitypay.pay&subject=%E5%85%85%E5%80%BC%E7%8C%AB%E8%B1%86&total_fee=0.01&sign=bjN4k5we10NWX5S1S1k5a7C%2FElHVFCXIxi%2FpiCRNpzNCw5X7Jfy6aSsgoGs0HFyKUk5ky58WHsJJaP8MOv4ySx0hWw5NB77g2WURmOOLQOQG%2FrkkbVYplxEaw2QbxFh93k1QzAHHwFY%2BU8tnu5hI3xnDq7iJvC8mcBQE9P6K%2FXfvI1K9fwarusXHkfoaTgeT763cOwDsbb0CoWQKmIKtI4IEK94BUYns%2FtQEQxxaGfAAzZhH%2BWEVUYQUUZLvjl2QZQhO3WoNgfEODb6%2Bt7y24fBx4yJ9wYn3gvXWmdqPaCc%2FHD7IoOsgMxkRYU8ypfcHL2ihb2ZxxVbXbYtEcpeK9A%3D%3D&sign_type=RSA"
  }
}

失败举例：
{
  "err": 400,
  "msg": "return url is illegal.",
}


=============================Alipay.php=======================================
app\Http\payment\Alipay;

<?php

namespace App\Http\Controllers\Payment;

use
    Illuminate\Support\Facades\Validator,
    Illuminate\Http\Request,
    App\Traits\Tool,
    Payment\Common\PayException,
    Payment\Client\Refund,
    Payment\Config,
    App\Models\Finance\RefundLog;

class Alipay implements \App\Contract\PaymentMethod
{
    public function prepare(array &$params)
    {
        $validate = Validator::make($params, [
            'client'    =>  'required|in:web,wap,mobile',
            'amount'    =>  'required|numeric|min:0.01',
            'notify'    =>  'required|url',
            'trade_no'  => 'required',
            'desc'      => 'required',
        ]);

        if ($validate->fails()) {
            return [
                'err'   =>  400，
                'msg'   =>  $Validator->errors()->first(),
            ];
        }

        return true;
    }

    public function pay(array &$params):array
    {
        if (true !== ($prepareRes = $this->prepare($params))) {
            return $prepareRes;
        }

        $amountEscape = in_array(env('APP_ENV'), ['local', 'test', 'stage'])
        ? 0.01
        : $params['amount'];

        $alipay = app('alipay.'.$aparams['client']);
        $alipay->setOutTradeNo($params['trade_no']);
        $alipay->serTotalFee($amountEscape);
        $alipay->serSubject($params['desc']);
        $alipay->setNotifyUrl($params['notify']);

        if (isset($params['body'])
            && is_string($params['body'])
            && $params['body']
        ) {
            $alipay->setBody($params['body']);
        }

        $fillPayDataHandler = 'fillPayDataFor'.ucfirst($params['client']);

        if (! method_exists($this, $fillPayDataHandler)) {
            return [
                'err'  =>  5001,
                'msg'  =>  Tool::sysMsg('MISSING_PAY_METHOD_HANDLER'),
            ];
        }

        return $this->$fillPayDataHandler($alipay, $params);
    }

    protected function fillpayDataForMobile(&$alipay, $params): array
    {
        return [
            'err'  =>  0,
            'msg'  =>  'ok',
            'dat'  =>  [
                'params'  =>  $alipay->getPayPara();
            ],
        ];
    }

    protected function fillPayDataForWeb(&$alipay, $params): array
    {
        $alipay->setAppPay('N');

        $validator = Validator::make($params, [
            'return' => 'required:url',
        ]);

        if ($validator->fails()) {
            return [
                'err'  =>  400,
                'msg'  =>  $Validator->errors()->first();

            ];
        }

        // Enable QR pay, optional
        // See: <https://doc.open.alipay.com/support/hotProblemDetail.htm?spm=a219a.7386797.0.0.LjEOn6&source=search&id=226728>
        if (isset($params['qrpay'])
            && in_array($params['qrpay'], [0, 1, 2, 3, 4])
            && method_exists($alipay, 'setQrPayMode')
        ) {
            $alipay->setQrPayMode($params['qrpay']);
        }

        $alipay->setReturnUrl($params['return']);

        return [
            'err' => 0,
            'msg' => 'ok',
            'dat' => [
                'url' => base64_encode($alipay->getPayLink()),
            ],
        ];
    }

    protected function fillPayDataForWap(&$alipay, $params): array
    {
        return $this->fillPayDataForWeb($alipay, $params);
    }

    public function tradeSuccess($client): bool
    {
        if (! in_array($client, ['wap', 'web', 'mobile'])) {
            return false;
        } elseif (! app('alipay.'.$client)->verify()) {
            return false;
        } elseif(! ($tradeStatus = ($_REQUEST['trade_status'] ?? false))
                || !in_array($tradeStatus, [
                    'TRADE_SUCCESS',
                    'TRADE_FINISHED'
        ])) {
            return false;
        }

        return true;
    }

    public function callback($transHook)
    {
    }

    protected function validate(array $params, array $rules)
    {
        $validator = Validator::make($params, $rules);

        if ($validator->fails()) {
            return [
                'err'  =>  400,
                'msg'  =>  $validator->errors()->first(),
            ];
        }

        return true;
    }

    // Alipay refund
    public function refund(array $params)
    {
        $createAt = time();
        $config   = config('custom')['alipay'] ?? [];

        if (true !== (
            $legalConfig = $this->validate($config,[
                'app_id'            =>  'required',
                'sign_type'         =>  'required|in:RSQ,RSA2',
                'use_sandbox'       =>  'required',
                'ali_public_key'    =>  'required',
                'rsa_provate_key'   =>  'required',
        ]))) {
            return $legalConfig;
        } elseif (true !== (
            $legalParams = $this->validate($params, [
                'paylog_id'  =>  'required|integer|min:1',
                'id_type'    =>  'required|in:trade_no,out_trade_no',
                'trade_no'   =>  'required',
                'amount'     =>  'required|numeric|min:0.01',
                'operator'   =>  'required',

        ]))) {
            return $legalParams;
        }

        try {
            $refundLog = RefundLog::wherePaylogId($params['paylog_id'])
                                    ->first();
            $refundNo = $refundLog
            ? $refundLog->refund_no
            : Tool::tradeNo(0, '04');

            $reason = $params['reason'] ?? Tool::sysMsg(
                'REFUND_REASON_COMMON'
            );


            $err  = 0;
            $msg  = 'ok';
            $data = [
                'refund_fe'  =>  $params['amount'],
                'reason'     =>  $reason,
                'refund_no'  =>  $refundNo,
            ];

            $data[$params['id_type']] = $params['trade_no'];

            $_data = [
                'refund_no'       => $refundNo,
                'paylog_id'       => $params['paylog_id'],
                'operator'        => $params['operator'],
                'reason_request'  =>  $reason,
            ]

            $ret = Refund::fun(Config::ALI_REFUND, $config, $data);

            $processAt = time();

            if (isset($ret['code']) && ('10000' == $ret['code'])) {
                $_data['process_at'] = date('Y-m-d H:i:s');
                $_data['status']     = 1;
            } else {
                $err  =  $ret['code'] ?? 5001;
                $msg  =  $reasonFail = $ret['msg'] ?? Tool::sysMsg(
                    'REFUND_REQUEST_FAILED'
                );
            }

            if (true !== ($updateOrInsertRefundLogRes = $this->updateOrInsertRefundLog(
                    $refundLog,
                    $_data,
                    $createAt
            ))) {
                return $updateOrInsertRefundLogRes;
            }

            return [
                'err' => $err,
                'msg' => $msg,
                'dat' => $ret,
            ];
        } catch (PayException $pe) {
            $status = ($refundLog && (1 == $refundLog->status)) ? 1 : 2;
            $_data['status']       =  $status;
            $_data['reason_fail']  =  $pe->getMessage();
            $_data['process_at']   =  data('Y-m-d H:i:s');

            if (true !== ($updateOrInsertRefundLogRes = $this->updateOrInsertRefundLog(
                    $refundLog,
                    $_data,
                    $createAt
            ))) {
                return $updateOrInsertRefundLogRes;
            }

            return [
                'err' => '500X',
                'msg' => $pe->getMessage();
            ];
        } catch (\Exception $e) {
            return [
                'err' => '503X',
                'msg' => $e->getMessage();
            ];
        }
    }

    protected function updateOrInsertRefundLog($refundLog, $data, $createAt)
    {
        if ($refundLog) {
            $processRes = $RefundLog::whereId($refundLog->id)
            ->update($data);
        } else {
            $_data['create_at'] = date('Y-m-d H:i:s', $createAt);
            $processRes = RefundLog::insert($data);
        }

        return $processRes ? true : [
            $err = 5002,
            $msg = Tool::sysMsg('DATA_UPDATE_ERROR')
        ];
    }
}





=============================Config============================================
latrell-alipay.php#

<?php
return [

    // 合作身份者id 以 `2088` 开头的16位纯数字
    'partner_id' => '2088xxxxxxxxxxxxx',

    // 卖家支付宝帐户
    'seller_id' => 'xxxx'
]


latrell-alipay-mobile.php#

<?php
return [

    // 安全检验码，以数字和字母组成的32位字符
    'key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',

    // 签名方式
    'sign_type' => 'RSA',

    // 商户私钥
    'private_key_path' => __DIR__ . '/key/private_key.pem',

    // 阿里公钥
    'public_key_path' => __DIR__ . '/key/public_key.pem',

    // 异步通知连接
    'notify_url' => 'http://xxx'
];

latrell-alipay-web.php#

<?php
return [

    // 安全检验码，以数字和字母组成的32位字符
    'key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',

    // 签名方式
    'sign_type' => 'MD5',

    // 服务器异步通知页面路径
    'notify_url' => 'http://xxx',

    // 页面跳转同步通知页面路径
    'return_url' => 'http://xxx'
];






=============================RefundLog=========================================

<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\PaymentLog;

class RefundLog extends Model
{
    protected $table = 'refund_log';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public function refundedAmount(int $paylogId)
    {
        return $this->select(
            \DB::raw('sum('.$this->table.'.amount) as amountRefunded')
        )
        ->wherePaylogIdAndStatus($paylogId, 1)
        ->first();
    }

    public function tradeAndRefundedAmount(
        int $paylogId,
        string $payment,
        $refundId = null
    ) {
        $paymentLog = $this->paymentLog()->tbName();
        $tradeAndRefundedAmount = RefundLog::select(
            \DB::raw('
                sum('.$this->table.'.amount) as amountRefunded,
                '.$payment.'.amount as amountTotal,
                '.$paymentLog.'.payment
            ')
        )
        ->leftJoin(
            $paymentLog,
            $paymentLog.'.log_id',
            '=',
            $this->table.'.paylog_id'
        )
        ->wherePaylogIdAndpayment($paylogId, $payment);

        if (! is_null($refundId)) {
            if (! is_integer($refundId) || (0 >= $refundId)) {
                return [
                    'err' => '503X',
                    'msg' => 'illegal refund log id.',
                ];
            }

            $tradeAndRefundedAmount = $tradeAndRefundedAmount
            ->whereId($refundId);
        }

        return $tradeAndRefundedAmount->first();
    }

    protected function paymentLog()
    {
        return new PaymentLog;
    }
}