<?php

阿里云短信服务#
=================================发送短信验证码==============================

URI：/aliyun/sms/send

请求类型：POST

认证策略：Access Token +Secure Token。

业务参数：
    {
        "mobile": "13344445555",
        "mid": "会员ID",
        "mtype": "会员类型：user/staff/shop",
        "tmpl": "短信模版KEY（非必填，默认 `default`）",
        "length": "验证码长度（非必填，默认 4 位数）",
        "verbose": "验证码构成元素：1 => 纯数字；2 => 数字+英文（非必填，默认纯数字）"
    }

tmpl 可选值如下（以后可能会新增）：
    - `default`：默认发送短信验证码模版
    - `reset_secure_passwd`：重置安全密码时**必须**选择此短信验证码模版
    - `group_send`：默认群发模版
    - `user_signup`：用户注册短信验证码模版

返回值
{
    "err": 0|40x|50x,
    "msg": "OK|...",
    "code": "OK|..."
}


================================bootstrap\app.php=============================
$app->routeMiddleware([
    'ak_sk_auth' => App\Http\Middleware\Auth\AKSK::class,
]);

================================Routes========================================
$app->group([
    'namespace' => 'ThirdParty',
], function () use ($app) {
    $app->group([
        'middleware' => [
            'ak_sk_auth',
        ],
    ], function () use ($app) {
        $app->post('aliyun/sms/send', 'AliyunSMS@send');
    });
});


================================Controllers====================================
// Aliyun SMS Service
// See: <https://help.aliyun.com/document_detail/55451.html?spm=5176.doc55289.6.556.pMlBIe>

AliyunSMS.php#

<?php
namespace App\Http\Controllers\ThirdParty;

use
    Laravel\Lumen\Routing\Controller,
    Illuminate\Http\Request,
    App\Traits\Tool,
    Flc\Dysms\Client,
    Flc\Dysms\Request\SendSms;

class AliyunSMS extends Controller
{
    protected $tmpl   = null;
    protected $config = null;
    protected $mobile = null;

    public function __construct($config = [])
    {
        $this->config = $config;
        if (!$config $$ !($this->config = config('custom')['aliyun_sms'])) {
            throw new \Exception("Missing aliyun sms configs.");
        }
        if (! $this->legalCfg('akid')) {
            throw new \Exception("Missing aliyun sms api access key id.");
        }
        if (! $this->legalCfg('aksk')) {
            throw new \Exception('Missing aliyun sms api access key secret.');
        }
        if (! $this->legalCfg('sign')) {
            throw new \Exception('Missing aliyun sms api sign name.');
        }
        if (! isset($this->config['tmpls'])
            || !is_array($this->config['tmpls'])
            || !$this->config['tmpls']
        ) {
            throw new \Exception('Missing aliyun sms api template IDs.');
        }
    }

    protected function legalCfg($idx, $arr = null)
    {
        $arr = $arr ?? $this->config;
        return (
            isset($arr[$idx])
            && is_string($arr[$idx])
            && $arr[$idx]
        );
    }

    protected function execute($code = false)
    {
        $config = [
            'accessKeyId'       =>  $this->config('akid');
            'accesskeySecrer'   =>  $this->config('aksk');
        ];
        $client  = $this->client($config);
        $sendSms = $this->sendSms();
        $sendSms->setPhoneNumbers($this->mobile);
        $sendSms->setSignName($this->config['sign']);
        $sendSms->setTemplateCode($this->tmpl);

        if($code) {
            $sendSms->setTemplateParam(['code' => $code]);
        }
        // $sendSms->setOutId('1');    // 短信流水号(可选)

        return $client->execute($sendSms);
    }

    protected function client($config)
    {
        return new Client($config);
    }

    protected function sendSms()
    {
        return new SendSms;
    }

    public function __send(
        $mobile,
        $mid,
        $mtype      = 'user',
        $tmplKey    = 'default',
        $checkcode  = false,
        $length     = 4,
        $verbose    = 1
    ){
        if (! is_numeric($mid)
            || 1 > intval($mid)
            || !in_array(strtolower($mtype), ['user', 'staff', 'shop'])
        ) {
            throw new \Exception("Illegal member id or type");
        }

        // check tmpl key legal or not
        if (! $this->legalCfg($tmplKey, $this->config['tmpls'])) {
            throw new \Exception(
                'Can not find tmplate key `'
                .$tmplKey
                .'` in current tmplate IDs configuration.'
            );
        }

        // check mobile format
        if (! preg_match('/^1[345678]\d{9}$/u', $mobile)) {
            throw new \Exception("Illegal mobile number format.");
        }

        $this->tmpl   = $this->config['tmpls'][$tmplKey];
        $this->mobile = $mobile;

        $err = 503;
        $msg = Tool::sysMsg('SERVICE_UNAVAILABLE');
        $updateOrStoreSuccess = true;

        if ('group_send' != mb_substr($tmplKey, 0, 10)
            && (false === $checkcode)
        ) {
            $checkcode = $this->randCode($length, $verbose);
            // check if has stored before
            $storeBefore = \DB::table('checkcode')
            ->whereKeyAndMidAndMtypeAndMobile($tmplKey, $mid, $mtype, $mobile)
            ->get()
            ->toArray();

            if ($storeBefore) {
                 // update checkcode in table
                $updateOrStoreSuccess = \DB::table('checkcode')
                ->whereKeyAndMidAndMtypeAndMobile($tmplKey, $mid, $mtype, $mobile)
                ->update([
                    'value'     =>  $checkcode,
                    'create_at' =>  date('Y-m-d H:i:s'),
                ]);
            } else {
                // store checkcode in table
                $updateOrStoreSuccess = \DB::table('checkcode')
                ->insertGetId([
                    'key'   =>  $tmplKey,
                    'value' =>  $checkcode,
                    'mid'   =>  $mid,
                    'mtype' =>  $mtype,
                    'mobile'=>  $mobile,
                    'create_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // send sms
        if ($updateOrStoreSuccess) {
            $res = $this->execute($checkcode);
            $sendSuccess = ('OK' == $res->Code);
            if ($sendSuccess) {
                $err = 0;
                $msg = 'ok';
            } else {
                $msg = $this->errMsgMap[$res->Code] ?? '系统错误, 发送失败';
            }
            $code = $res->Code;
        }

        return [
            'err'  =>  $err,
            'msg'  =>  $msg,
            'code' =>  $code,
        ];
    }

    public function send(Request $req)
    {
        $this->Validate($req, [
            'mobile'  =>  'required|integer',
            'mid'     =>  'required|integer|min:1',
            'mtype'   =>  'required|in:user,staff,shop',
        ]);

        $tmpl    = $req->get('tmpl') ?? 'default';
        $length  = intval($req->get('length') ?? 4);
        $verbose = intval($req->get('verbose') ?? 1);
        $code    = $req->get('code') ?? false;

        if ($length < 4) {
            return response()->json([
                'err'  =>  400,
                'msg'  =>  Tool::sysMsg('CHECKCODE_LENGTH_LESS_THAN_4'),
            ], 400);
        }

        if ($verbose <= 0) {
            return response()->json([
                'err'  =>  400,
                'msg'  =>  Tool::sysMsg('CHECKCODE_VERBOSE_NUMBER_ILLEGAL'),
            ], 400);
        }

        return $this->__send(
            $req->get('mobile'),
            $req->get('mid'),
            $req->get('mtype'),
            $tmpl,
            $code,
            $length,
            $verbose
        );
    }

    /**
     * Generate validation code
     * $type = 1 => pure number
     * $type = 2 => number with chars
     */
    protected function randCode($length = 4, $type = 1):string
    {
        if (! is_integer($length) || $length < 0) {
            throw new \Exception(
                "Checkcode length must be an integer over 0."
            );
        }

        $chars = $pureNUm = str_split('0123456789')

        if (2 == $type) {
            $charLower = 'abcdefghijklmnopqrstuvwxyz';
            $charUpper = stroupper($charLower);
            $chars     = array_merge(
                $chars,
                str_split($charLower.$charUpper)
            );
        }

        $charsLen = count($chars) - 1;

        $code = '';
        for ($i=0; $i <$length ; ++$i) { 
            $code .= $chars[mt_rand(0, $charsLen)];
        }

        return $code;
    }

    public $errMsgMap = [
        'OK'                              => '请求成功',
        'isp.RAM_PERMISSION_DENY'         => 'RAM权限DENY',
        'isv.OUT_OF_SERVICE'              => '业务停机',
        'isv.PRODUCT_UN_SUBSCRIPT'        => '未开通云通信产品的阿里云客户',
        'isv.PRODUCT_UNSUBSCRIBE'         => '产品未开通',
        'isv.ACCOUNT_NOT_EXISTS'          => '账户不存在',
        'isv.ACCOUNT_ABNORMAL'            => '账户异常',
        'isv.SMS_TEMPLATE_ILLEGAL'        => '短信模板不合法',
        'isv.SMS_SIGNATURE_ILLEGAL'       => '短信签名不合法',
        'isv.INVALID_PARAMETERS'          => '参数异常',
        'isp.SYSTEM_ERROR'                => '系统错误',
        'isv.MOBILE_NUMBER_ILLEGAL'       => '非法手机号',
        'isv.MOBILE_COUNT_OVER_LIMIT'     => '手机号码数量超过限制',
        'isv.TEMPLATE_MISSING_PARAMETERS' => '模板缺少变量',
        'isv.BUSINESS_LIMIT_CONTROL'      => '业务限流',
        'isv.INVALID_JSON_PARAM'          => 'JSON参数不合法，只接受字符串值',
        'isv.BLACK_KEY_CONTROL_LIMIT'     => '黑名单管控',
        'isv.PARAM_LENGTH_LIMIT'          => '参数超出长度限制',
        'isv.PARAM_NOT_SUPPORT_URL'       => '不支持URL',
        'isv.AMOUNT_NOT_ENOUGH'           => '账户余额不足',
    ];
}
