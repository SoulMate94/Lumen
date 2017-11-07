<?php

// Global CORS control

namespace App\Http\Middleware;

use Closure;

class CORS
{
    public function __construct()
    {
    }

    public function handle($request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => implode(',', [
                'Access-Control-Allow-Origin',
                'AUTHORIZATION',
                'HCM-API-TYPE',
                'HCM-API-AK',
                'HCM-API-Sk',
            ]),
            'Access-Control-Max-Age'        =>  86400,
        ];

        if ('OPTIONS' == $request->getMethod()) {
            return response(null, 200, $headers);
        }

        return $next($request)->withHeaders($headers);

    }
}