<?php

namespace App\Http\Middleware\Auth;

use
    App\Traits\Tool,
    Closure;

class JWTSimple
{
    use \App\Traits\SimpleJWT;

    protected $auth = false;

    public function __construct()
    {
        $this->auth = $this->authorise();
    }

    public function handle($request, Closure $next)
    {
        if (false === $this->auth) {
            return response()->json([
                'err' => 401,
                'msg' => Tool::sysMsg('UNAUTHORIZED'),
            ], 401);
        }

        $request->attributes->add([
            'simple_jwt_auth' => $this->auth,
        ]);

        return $next($request);
    }
}