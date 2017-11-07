<?php

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
