<?php

支付宝支付退款#

===========================Api文档=============================================
API 系统调用举例：
use App\Http\Controllers\Payment\Alipay;
public function refundTest(Alipay $alipay)
{
    // 从交易日志表中查出支付日志ID以及要退款的支付宝订单号（`id_type` = 'trade_no'）
    // 获取退款操作者
    $params = [
        'paylog_id' => 123456,
        'id_type' => 'trade_no',
        'trade_no' => '2017XXXX',
        'amount' => 0.01,
        'operator' => 'admin:1'
    ];
    $res = $alipay->refund($params);
    if (0 === $res['err']) {
        // 退款成功的逻辑
    } else {
        // dd($res);
        // 退款失败的逻辑
    }
}



============================Alipay.php========================================
public function refund(array $params)
    {
        $createAt = time();
        $config   = config('custom')['alipay'] ?? [];

        if (true !== (
            $legalConfig = $this->validate($config, [
                'app_id'          => 'required',
                'sign_type'       => 'required|in:RSA,RSA2',
                'use_sandbox'     => 'required',
                'ali_public_key'  => 'required',
                'rsa_private_key' => 'required',
        ]))) {
            return $legalConfig;
        } elseif (true !== ($legalParams = $this->validate($params, [
            'paylog_id'=> 'required|integer|min:1',
            'id_type'  => 'required|in:trade_no,out_trade_no',
            'trade_no' => 'required',
            'amount'   => 'required|numeric|min:0.01',
            'operator' => 'required',
        ]))) {
            return $legalParams;
        }

        try {
            $refundLog = RefundLog::wherePaylogId($params['paylog_id'])->first();

            $refundNo = $refundLog
            ? $refundLog->refund_no
            : Tool::tradeNo(0, '04');

            $reason = $params['reason'] ?? Tool::sysMsg(
                'REFUND_REASON_COMMON'
            );

            $data = [
                'refund_fee' => $params['amount'],
                'reason'     => $reason,
                'refund_no'  => $refundNo,
            ];

            $data[$params['id_type']] = $params['trade_no'];

            $_data = [
                'refund_no'  => $refundNo,
                'paylog_id'  => $params['paylog_id'],
                'operator'   => $params['operator'],
                'reason_request' => $reason,
            ];

            $err = 0;
            $msg = 'ok';

            $ret = Refund::run(Config::ALI_REFUND, $config, $data);

            $processAt = time();

            if (isset($ret['code']) && ('10000' == $ret['code'])) {
                $_data['process_at']  = date('Y-m-d H:i:s');
                $_data['status'] = 1;
            } else {
                $err = $ret['code'] ?? 5001;
                $msg = $reasonFail = $ret['msg'] ?? Tool::sysMsg(
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
            $_data['status']      = $status;
            $_data['reason_fail'] = $pe->getMessage();
            $_data['process_at']  = date('Y-m-d H:i:s');

            if (true !== ($updateOrInsertRefundLogRes = $this->updateOrInsertRefundLog(
                $refundLog,
                $_data,
                $createAt
            ))) {
                return $updateOrInsertRefundLogRes;
            }

            return [
                'err' => '500X',
                'msg' => $pe->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'err' => '503X',
                'msg' => $e->getMessage(),
            ];
        }
    }

    protected function updateOrInsertRefundLog($refundLog, $data, $createAt)
    {
        if ($refundLog) {
            $processRes = RefundLog::whereId($refundLog->id)
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
