<?php

namespace App\Traits;

trait CURL
{
    public function requestJsonApi($uri, $type = 'POST', $params = [])
    {
        $ch = curl_init();

        $setOpt = [
            CURLOPT_URL        => $uri,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; Charset: UTF-8',
            ],
            CURLOPT_RETURNTRANSFER => true,
        ];

        if ('POST' == $type) {
            $setOpt = array_merge($setOpt, [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => $params,
            ]);
        }

        curl_setopt_array($ch, $setOpt);

        $res = curl_exec($ch);

        $errNo  = curl_errno($ch);
        $errMsg = curl_error($ch);

        curl_close($ch);

        return [
            'err' => $errNo,
            'msg' => $errMsg,
            'res' => json_decode($res, true),
        ];
    }
}
