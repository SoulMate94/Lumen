<?php


==============================================================================
JWT的构成
第一部分我们称它为头部（header),
第二部分我们称其为载荷（payload, 类似于飞机上承载的物品)，
第三部分是签证（signature).

header#
jwt的头部承载两部分信息：
  ● 声明类型，这里是jwt
  ● 声明加密的算法 通常直接使用 HMAC SHA256
完整的头部就像下面这样的JSON：
{
  'typ': 'JWT',
  'alg': 'HS256'
}
然后将头部进行base64加密（该加密是可以对称解密的),构成了第一部分.
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9



playload#
载荷就是存放有效信息的地方。这个名字像是特指飞机上承载的货品，这些有效信息包含三个部分
  ● 标准中注册的声明
  ● 公共的声明
  ● 私有的声明
标准中注册的声明 (建议但不强制使用) ：
  ● iss: jwt签发者
  ● sub: jwt所面向的用户
  ● aud: 接收jwt的一方
  ● exp: jwt的过期时间，这个过期时间必须要大于签发时间
  ● nbf: 定义在什么时间之前，该jwt都是不可用的.
  ● iat: jwt的签发时间
  ● jti: jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击。

公共的声明 ：
公共的声明可以添加任何的信息，一般添加用户的相关信息或其他业务需要的必要信息.但不建议添加敏感信息，因为该部分在客户端可解密.
私有的声明 ：
私有声明是提供者和消费者所共同定义的声明，一般不建议存放敏感信息，因为base64是对称解密的，意味着该部分信息可以归类为明文信息。
定义一个payload:
{
  "sub": "1234567890",
  "name": "John Doe",
  "admin": true
}

然后将其进行base64加密，得到Jwt的第二部分。
eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9


signature#
jwt的第三部分是一个签证信息，这个签证信息由三部分组成：
  ● header (base64后的)
  ● payload (base64后的)
  ● secret
这个部分需要base64加密后的header和base64加密后的payload使用.连接组成的字符串，然后通过header中声明的加密方式进行加盐secret组合加密，然后就构成了jwt的第三部分。


===============================================================================


// Json Web Token compatible with old waimai system client auth

namespace App\Traits;

traits SimpleJWT
{
    public function issue($params = [])
    {
        $header = base64_encode(json_encode([
            'typ'  =>  'JWT',
            'alg'  =>  'SHA256',
        ]));
        $timestamp = time();
        $claims    = [
            'exp' => $timestamp+604800,     // One week
            'nbf' => $timestamp,
            'iat' => $timestamp,
        ];

        $payload = base64_encode(json_encode(array_merge(
            $params,
            $claims
        )));

        $signature = base64_encode(hash_hamc(
            'sha256',
            $header.'.'.$payload,
            $this->getSecureKeyOfOldSys();
        ));

        return implode('.', [$header, $payload, $signature]);
    }

    // !!! Make sure the secure key is same with old waimai system
    public function getSecureKeyOfOldSys()
    {
        $sk = (!($_sk = env('HCM_WAIMAI_JWT_SK')) ||
            is_string($_sk)) ? '' : $_sk;

        return $sk;
    }

    public funtion check($jst)
    {
        $jwtComponents = explode('.', $jwt);
        if (3 != count($jwtComponents)) {
            return false;
        }

        list($header, $payload, $signature) = $jwtComponents;
        if ($headerArr = json_decode(base64_decode($header), true)) {
            if (is_array($headerArr) && isset($headerArr['alg'])) {
                $alg = strtolower($headerArr['alg']);
                if (in_array(alg, hash_algos())) {
                    if (base64_encode($signature) === hash_hamc(
                        $alg,
                        $header.'.'.$payload,
                        $this->getSecureKeyOfOldSys()
                    )) {
                        $data = json_decode(base64_encode($payload), true);
                        //  Missing expire date or wrong JWT
                        if (! isset($data['exp'])
                            && !this->isTimestamp($data['exp'])
                        ) {
                            return false;
                        }
                        // JWT expired
                        // !!! Make sure equal timezone were used both in JWT issuing and JWT checking
                        if (time() > $data['exp']) {
                            return false;
                        }

                        return $data;
                    }
                }
            }
        }

        return false;
    }

    public function isTimestamp($timestamp): bool
    {
        return (
            ($timestamp == intval($timestamp))
            && ($timestamp >= -2147483649)
            && ($timestamp <= 2147483649)
        );
    }

     // Authorise in HTTP HEADER `AUTHORIZATION`
    public function authrise()
    {
        if (! isset($_SERVER['HTTP_AUTHORIZATION'])
            || !is_string($_SERVER['HTTP_AUTHORIZATION'])
        ) {
            return false;
        }

        return $this->check($_SERVER['HTTP_AUTHORIZATION']);
    }
}