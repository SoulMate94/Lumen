<?php

================Admin========================
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
            'admin_auth' => $this->auth,
        ]);

        return $next($request);
    }
}


================AKSK========================
class AKSK
{
    protected $auth = false;

    public function auth($request)
    {
        $readonly = (
            isset($_SERVER['HTTP_HCM_API_TYPE'])
            && ('READ' == strtoupper($_SERVER['HTTP_HCM_API_TYPE']))
        ) || in_array($request->method(), [
            'HEAD', 'OPTIONS', 'GET'
        ]);

        try {
            if (isset($_SERVER['HTTP_HCM_API_AK'])
                && ($ak = $_SERVER['HTTP_HCM_API_AK'])
                && is_string($ak)
            ) {
                $legalAK = (env('HCM_API_AK') == $ak);
                $legalSK = $readonly ? true : (
                    (
                        isset($_SERVER['HTTP_HCM_API_SK'])
                        && ($sk = $_SERVER['HTTP_HCM_API_SK'])
                        && is_string($sk)
                    ) ? (env('HCM_API_SK') == $sk) : false
                );

                $this->auth = $legalAK && $legalSK;
            }
        } catch (\Exception $e) {
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

================JWT========================
class JWT
{
    use \App\Traits\SimpleJWT;

    protected $auth = false;

    public function auth()
    {
        try {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])
                && ($jwt = $_SERVER['HTTP_AUTHORIZATION'])
            ) {
                $this->auth = FirebaseJWT::decode(
                    $jwt,
                    env('SERECT_KEY'),
                    ['HS256']
                );
            }
        } catch (\Exception $e) {
            $this->auth = $this->authorise();
        } finally {
        }

        return $this->auth;
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
            'jwt_auth' => $this->auth,
        ]);

        return $next($request);
    }
}


================JWTSimple======================
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


================Qiniu==========================
class Qiniu
{
    private $ak = false;
    private $sk = false;
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
        } catch (\Exception $e) {
        } finally {
        }
    }

    public function handle($request, Closure $next)
    {
        if ((false !== $this->ak) && (false !== $this->sk)) {
            $request->attributes->add([
                'qiniu_ak'     => $this->ak,
                'qiniu_sk'     => $this->sk,
                'qiniu_bucket' => $this->bucket,
                'qiniu_auth' => new Auth($this->ak, $this->sk),
            ]);
            return $next($request);
        }

        return response()->json([
                'error' => 'missing access key or secret key'
            ], 500);
    }
}


================CheckUserJwt==========================
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
        //验证存在auth,并且不是int型
        if (
            !array_key_exists('HTTP_AUTHORIZATION',$_SERVER) ||
            is_numeric($_SERVER['HTTP_AUTHORIZATION']) ||
            strlen($_SERVER['HTTP_AUTHORIZATION']) <= 110
        ) {
            return response()->json([
                'errcode' => 1080,
                'message' => 'request not allow',
            ]);
        }

        try {
            //反解token
            $object = JWT::decode($_SERVER['HTTP_AUTHORIZATION'], env('SERECT_KEY'), array('HS256'));
            //判断有效期
            if (!isset($object->expires) || $object->expires - time() <= 0) {
                return response()->json([
                        'errcode' => 1081,
                        'message' => 'token expires timeout,please sign in again'
                    ]
                );
            }

            $request->attributes->add([
                'agent_id' => $object->sub
            ]);

            return $next($request);

        }catch(\UnexpectedValueException $e) {
            return response()->json([
                    'errcode' => 1082,
                    'message' => 'not allow'
                ]
            );
        }catch(SignatureInvalidException $e) {
            return response()->json([
                    'errcode' => 1083,
                    'message' => 'illegal request'
                ]
            );
        }
    }
}


================CORS=============================
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
                'HCM-API-SK',
            ]),
            'Access-Control-Max-Age'       => 86400,
        ];

        if ('OPTIONS' == $request->getMethod()) {
        	return response(null, 200, $headers);
        }

        return $next($request)->withHeaders($headers);
	}
}
