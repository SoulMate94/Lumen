<?php


微信支付退款#

===========================Api文档=============================================
API 内部调用举例：
use App\Http\Controllers\Payment\Wxpay;
class RefundText
{
    public function refund(Wxpay $wxpay)
    {
        // 获得要退款的 已使用微信支付成功的 交易日志 ID => $paylogId
        // 要退款的金额：$amount
        $params = [
            'paylog_id' => $paylogId,
            'id_type' => 'transaction_id',
            'amount' => $amount,
            'trade_no' => '101500133600201709145121705062',
            'operator' => 'admin:1',
        ];
        return $wxpay->refund($params);
    }
}


============================Wxpay.php========================================

protected function refundLog()
    {
        return new RefundLog;
    }

public function refund(array $params)
{
    if (true !== ($configCheckRes = $this->checkConfig())) {
        return $configCheckRes;
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
            $amountCanbeRefunded = abs($amountTotal - $amountRefunded);

            if ($amountTotal < $amountCanbeRefunded) {
                return [
                    'err' => 5002,
                    'msg' => Tool::sysMsg('REFUND_AMOUNT_ILLEGAL'),
                ];
            } elseif ($amountRefunded >= $amountTotal) {
                return [
                    'err' => 5003,
                    'msg' => Tool::sysMsg('REFUNDED_ALL_ALREADY'),
                ];
            }
        }

        $data = [
            'service'    => 'unified.trade.refund',
            'mch_id'     => $this->config['mchid'],
            'total_fee'  => $amountTotal*100,
            'refund_fee' => $params['amount']*100,
            'op_user_id' => 'mch_wxpay_program',    // static
            'nonce_str'  => $this->nonceStr(),
            'out_refund_no'  => Tool::tradeNo(0, '04'),
        ];
        $data[$params['id_type']] = $params['trade_no'];
        $data['sign']             = $this->sign($data);

        $xml = Tool::arrayToXML($data);

        $res = $this->requestHTTPApi(
            $this->config['gateway'],
            'POST', [
                'Content-Type: application/xml; Charset: UTF-8',
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
            'refund_no'      => $data['out_refund_no'],
            'paylog_id'      => $params['paylog_id'],
            'amount'         => $params['amount'],
            'reason_request' => $reason,
            'operator'       => $params['operator'],
            'create_at'      => $createAt,
            'process_at'     => $processAt,
        ];

        $refundSuccess = false;
        if (isset($res['dat']['status'])
            && (0 == $res['dat']['status'])
            && isset($res['dat']['result_code'])
            && (0 == $res['dat']['result_code'])
            && isset($res['dat']['refund_id'])
        ) {
            // Insert or update into refund log
            $_data['status'] = 1;
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
                'err'  => 0,
                'msg'  => 'ok',
            ];
        } elseif ($errMsg) {
            $_data['status'] = 2;
            $_data['reason_fail'] = $errMsg;

            if (! $refundLog->insert($_data)) {
                return [
                    'err' => '503X',
                    'msg' => Tool::sysMsg('DATA_UPDATE_ERROR'),
                ];
            }

            return [
                'err'  => 5004,
                'msg'  => $errMsg,
            ];
        } else {
            return [
                'err'  => 5005,
                'msg'  => Tool::sysMsg('REFUND_REQUEST_FAILED'),
            ];
        }
    } catch (\Exception $e) {
        return [
            'err' => '500X',
            'msg' => $e->getMessage(),
        ];
    }
}