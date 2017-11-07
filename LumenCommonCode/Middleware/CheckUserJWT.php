<?php

======================Middleware===============================================

namespace App\Http\Middleware;

use Closure,
    Firebase\JWT\JWT,
    Firebase\JWT\SignatureInvaliException;

class CheckUserJwt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 验证存在auth, 并且不是int型
        if (
            !array_key_exists('HTTP_AUTHORIZATION', $_SERVER) ||
            is_numeric($_SERVER['HTTP_AUTHORIZATION']) ||
            strlen($_SERVER['HTTP_AUTHORIZATION']) <= 110
        ) {
            return response()->json([
                'err' => 1080,
                'msg' => 'request not allow',
            ]);
        }

        try {
            // 反解token
            $object = JWT::decode($_SERVER['HTTP_AUTHORIZATION']. env('SERECT_KEY'), array(HS256));
            // 判断有效期
            if (! isset($object->expires) ||
                $object->expires - time() <= 0
            )) {
                return response()->json([
                    'err' => 1081,
                    'msg' => 'token expires timeout,please sign in again'
                ]);
            }

            $request->attributes->add([
                'agent_id' => $object->sub
            ]);

            return $next($request);

        } catch (\UnexpectedValueException $e) {
            return response()->json([
                'err' => 1082,
                'mag' => 'not allow.'
            ]);
        } catch(SignatureInvaliException $e) {
            return response()->json([
                'err' => 1083,
                'msg' => 'illegal request.'
            ])
        }
    }
}