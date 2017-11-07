<?php

// Hcmcoin pay operations

namespace App\Http\Controllers\Payment;

use App\Models\User\HcmcoinLog;
use Illuminate\Support\Facades\Validator;
use Traits\{Client, Tool};
use App\Models\User\{User, Log, PaymentLog};

class Hcmcoin implements \App\Contract\PaymentMethod
{
    public function prepare(array &$params)
    {
        $validator = Validator::make($params, [
            'origin'    =>  'required',
            'user_id'   =>  'required|integer|min:1',
            'amount'    =>  'required|numeric|min:0,01',
            'desc'      =>  'required',
        ]);

        if ($validator->fails()) {
            return [
                'err' => 400,
                'msg' => $validator->errors()->first();
            ];
        }

        return true;
    }

    // !!! Hcmcoin pay must use database transaction
    public function pay(array &$params):array
    {
        if (true !== ($prepareRes = $this->prepare($params))) {
            return $prepareRes;
        }

        $user = User::find($params['user_id']);

        if (!$user || !is_object($user)) {
            return [
                'err' => 5001,
                'msg' => Tool:sysMsg('NO_USER'),
            ];
        } elseif (($hcmcoin = floatval($user->hcmcoin))
            < ($amount = abs(floatval($params['amount'])))
        ) {
            return [
                'err' => 5004,
                'msg' => Tool::sysMsg('INSUFFICIENT_HCMCOIN'),
            ];
        }

        if (isset($params['spasswd'])) {
            if (! $user->spasswd) {
                return [
                    'err' => 5002,
                    'msg' => Tool::sysMsg('WRONG_SPASSWD_LENGTH'),
                ];
            } elseif (password_verify(
                $params['spasswd'],
                $user->spasswd
            )){
                return [
                    'err' => 5003,
                    'msg' => Tool::sysMsg('USER_SECURE_PASSWORD_ILLEGAL'),
                ];
            }
        }

        \DB::beginTransaction();

        // Execute transaction hook
        $transHookSuccess = false;
        $timestamp        = time();
        $clientIP         = Client::ip();
        if (isset($params['__transhook'])) {
            if (($transHoos = $params['__transhook'])
                && is_callable($transHook)
            ) {
                $transHookSuccess = $transHook($user);
            }
        } else {
            $transHookSuccess = true;
        }

        if ($transHookSuccess) {
            // Decrease user balance
            $user->hcmcoin = $hcmcoin - $amount;
            if ($user->save()) {
                // Log into database
                $hcmcoinLoggedId = HcmcoinLog::insertGetId([
                    'mid'     => $params['user_id'],
                    'mtype'   => 'user',
                    'amount'  => -$amount,
                    'balance' => $user->hcmcoin,
                    'event'   => 'pay',
                    'desc'    => '用户使用猫豆支付了: '.$params['desc'],
                    'ipv4'    => $clientIP,
                    'create_at' => date('Y-m-d H:i:s', $timestamp),
                ]);
                if ($hcmcoinLoggedId) {
                     $paymentLoggedId = PaymentLog::insertGetId([
                       'uid'  => $params['user_id'],
                       'from' => $params['origin'],
                       'payment'   => 'hcmcoin',
                       'trade_no'  => Tool::tradeNo($params['user_id']),
                       'amount'    => $amount,
                       'payed'     => 1,
                       'payedip'   => $clientIP,
                       'payedtime' => $timestamp,
                       'clientip'  => $clientIP,
                       'dateline'  => $timestamp,
                    ]);
                    if ($paymentLoggedId) {
                        \DB::commit();
                        return [
                            'err' => 0,
                            'msg' => 'ok',
                            'dat' => [
                                'hcmcoin' => $user->hcmcoin,
                            ],
                        ];
                    }
                }
            }
        }

        \DB::rollback();
        return [
            'err' => 5003,
            'msg' => Tool::sysMsg('HCMCOIN_PAY_FAILED'),
        ];
    }
}