<?php

base64_encode-- 使用 MIME base64 对数据进行编码
issue--问题
hash_hmac--使用 HMAC 方法生成带有密钥的哈希值
string hash_hmac ( string $algo , string $data , string $key [, bool $raw_output = false ] )



namespace App\Traits;

trait SimpleJWT
{
    public function issue($params = [])
    {
        $header  = base64_encode(json_encode([
            'typ' => 'JWT',
            'alg' => 'SHA256',
        ]));
        $timestamp = time();
        $claims = [
            'exp' => $timestamp+3600,
            'nbf' => $timestamp,
            'iat' => $timestamp,
        ];
        $payload    = base64_encode(json_encode(array_merge(
            $params,
            $claims
        )));
        $signature  = base64_encode(hash_hmac(
            'sha256',
            $header.'.'.$payload,
            $this->getSecureKeyOfOldSys()
        ));

        return implode('.', [$header, $payload, $signature]);
    }

    // !!! Make sure the secure key is same with old waimai system
    public function getSecureKeyOfOldSys()
    {
        $sk = (!($_sk = env('HCM_WAIMAI_JWT_SK')) || !is_string($_sk))
        ? '' : $_sk;

        return $sk;
    }

    public function check($jwt)
    {
        $jwtComponents = explode('.', $jwt);
        if (3 != count($jwtComponents)) {
            return false;
        }

        // list — 把数组中的值赋给一组变量
        // hash_algos--返回已注册的哈希算法列表
        list($header, $payload, $signature) = $jwtComponents;
        if ($headerArr = json_decode(base64_decode($header), true)) {
            if (is_array($headerArr) && isset($headerArr['alg'])) {
                $alg = strtolower($headerArr['alg']);
                if (in_array($alg, hash_algos())) {
                    if (base64_decode($signature) === hash_hmac(
                        $alg,
                        $header.'.'.$payload,
                        $this->getSecureKeyOfOldSys())
                    ) {
                        // TODO check timestamp
                        return json_decode(base64_decode($payload), true);
                    }
                }
            }
        }

        return false;
    }

    // authorise--授权

    // Authorise in HTTP HEADER `AUTHORIZATION`
    public function authorise()
    {
        if (! isset($_SERVER['HTTP_AUTHORIZATION'])
            || !is_string($_SERVER['HTTP_AUTHORIZATION'])
        ) {
            return false;
        }

        return $this->check($_SERVER['HTTP_AUTHORIZATION']);
    }
}
