<?php

namespace App\Http\Middleware\Auth;

use
    App\Traits\Session,
    Closure,
    Firebase\JWT\JWT as FirebaseJWT;

class Admin
{
    protected $auth = false;

    public function __construct(Session $ssn)
    {
        $this->auth = $ssn->get('admin');
    }

    public function handle($request, Closure $next)
    {
        if (false === $this->auth) {
            return redirect()->route('admin_login');
        }

        $request->attributes->add([
            'admin_auth' => $this->auth;
        ]);

        return $next($request);
    }
}
