<?php

=====================Client=============================
class Client
{
    public static function ip()
    {
        if ($clientIP = self::tryIPKey('HTTP_CLIENT_IP')) {
        } elseif ($clientIP = self::tryIPKey('HTTP_X_FORWARDED_FOR')) {
        } elseif ($clientIP = self::tryIPKey('HTTP_X_FORWARDED')) {
        } elseif ($clientIP = self::tryIPKey('HTTP_FORWARDED_FOR')) {
        } elseif ($clientIP = self::tryIPKey('HTTP_FORWARDED')) {
        } elseif ($clientIP = self::tryIPKey('REMOTE_ADDR')) {
        } else $clientIP = 'UNKNOWN';

        return $clientIP;
    }

    public static function tryIPKey(string $possibleKey)
    {
        return getenv($possibleKey)
        ?? (
            $_SERVER[$possibleKey] ?? null
        );
    }

    public static function isMobile()
    {
        if (! ($ua = $_SERVER['HTTP_USER_AGENT']) || !is_string($ua)) {
            return false;
        }
    }
}

=====================CURL=============================
trait--特质
curl_init--初始化CURL会话
curl_setopt_array--为CURL传输会话批量设置选项
curl_exec--执行CURL会话
curl_errno--返回最后一次的错误代码
curl_error--返回当前会话最后一次错误的字符串
curl_close--关闭CURL会话

namespace App\Traits;

trait CURL
{
    public function requestJsonApi(
        $uri,
        $type = 'POST',
        $params = [],
        $headers = []
    ) {
        $headers = [
            'Content-Type: application/json; Charset: UTF-8',
        ];

        $res = $this->requestHTTPApi($uri, $type, $headers, $params);

        if (! $res['err']) {
            $res['res'] = json_decode($res['res'], true);
        }

        return $res;
    }

    public function requestHTTPApi(
        string $uri,
        string $type = 'GET',
        array $headers = [],
        $data
    ) {
        $setOpt = [
            CURLOPT_URL            => $uri,
            CURLOPT_RETURNTRANSFER => true,
        ];

        if ($headers) {
            $setOpt[CURLOPT_HTTPHEADER] = $headers;
        }

        if ('POST' == $type) {
            $setOpt[CURLOPT_POST]       = true;
            $setOpt[CURLOPT_POSTFIELDS] = $data;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $setOpt);
        $res = curl_exec($ch);

        $errNo  = curl_errno($ch);
        $errMsg = curl_error($ch);

        curl_close($ch);

        return [
            'err' => $errNo,
            'msg' => ($errMsg ?: 'ok'),
            'res' => $res,
        ];
    }
}