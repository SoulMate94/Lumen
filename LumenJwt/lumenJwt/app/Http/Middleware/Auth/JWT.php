<?php

namespace App\Http\Middleware\Auth;

use
    Closure,
    App\Traits\Tool,
    Firebase\JWT\JWT as FirebaseJWT;

class JWT
{
    use \App\Traits\SimpleJWT;

    protected $auth = false;

    public function auth()
    {
        try {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])
                && ($jwt =$_SERVER['HTTP_AUTHORIZATION'])
            ) {
                $this->auth = FirebaseJWT::decode(
                    $jwt,
                    env('SERECT_KEY'),
                    ['HS256']
                );
            }
        } catch(\Exception $e) {
            $this->auth = $this->authorise();
        } finally {

        }
        return $this->auth();
    }

    public function handle($request, Closure $next)
    {
        if (false === $this->auth()) {
            return response()->json([
                'err' => 401,
                'msg' => Tool::sysMsg('UNAUTHORIZED'),
            ], 401);
        }

        $request->attributes->add([
            'jwt_auth' => $this->auth(),
        ]);

        return $next($request);
    }
}

