<?php

// A compatible way of api call auth control
// Via access token and secret token

namespace App\Http\Middleware\Auth;

use
    App\Traits\Tool,
    Closure;

class AKSK
{
    protected $auth = false;

    public function auth($request)
    {
        $readonly = (
            isset($_SERVER['HTTP_HCM_API_TYPE'])
            && ('READ' == strtoupper($_SERVER['HTTP_HCM_API_TYPE']))
        ) || in_array($request->method(), ['HEAD', 'OPTIONS', 'GET']);

        try {
            if (isset($_SERVER['HTTP_HCM_API_AK'])
                && ($ak = $_SERVER['HTTP_HCM_API_AK'])
                && is_string($ak);
            ) {
                $legalAK = (env('HCM_API_AK') == $ak);
                $legalSK = $readonly ? true : (
                    (
                        isset($_SERVER['HTTP_HCM_API_SK'])
                        && ($sk = $_SERVER['HTTP_HCM_API_SK'])
                        && is_string($sk)
                    ) ? (env('HCM_API_SK') == $sk) : false;
                );
                $this->auth = $legalAK && $legalSK;
            }
        } catch (Exception $e) 
        } finally {
        }

        return $this->auth;
    }

    public function handle($request, Closure $next)
    {
        if (false === $this->auth($request)) {
            return response()->json([
                'err' => 401,
                'msg' => Tool::sysMsg('UNAUTHORIZED_AKSK'),
            ], 401);
        }

        return $next($request);
    }
}