<?php

// Qiniu service auth control
// Via access token and secret token

namespace App\Http\Middleware\Auth;

use Closure;
use \Qiniu\Auth;

class Qiniu
{
    private $ak     = false;
    private $sk     = false;
    private $bucket = null;

    public function __construct()
    {
        try {
            list(
                $this->ak,
                $this->sk,
                $this->bucket,
            ) = [
                env('QINIU_AK', false),
                env('QINIU_SK', false),
                env('QINIU_BUCKET', 'beta'),
            ];
        } catch (Exception $e) {
        } finally {
        }
    }

    public function handle($request, Closure $next)
    {
        if ((false !== $this->ak) && (false !== $this->sk)) {
            $request->attributes->add([
                'qiniu_ak'      =>  $this->ak,
                'qiniu_sk'      =>  $this->sk,
                'qiniu_bucket'  =>  $this->bucket,
                'qiniu_auth'    =>  new Auth($this->ak, $this->sk),
            ]);

            return $next($request);
        }

        return response()->json([
            'err'  =>  'missing access key or secret key'
        ], 500);
    }
}