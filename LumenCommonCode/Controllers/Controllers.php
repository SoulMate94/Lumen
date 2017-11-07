<?php

==============命名空间====================
namespace App\Http\Controllers\Admin;

namespace App\Http\Controllers;

namespace App\Http\Controllers\System;

namespace App\Http\Middleware\Auth;

==============类的引入====================
use Illuminate\Http\Request;

use
	App\Traits\Session,
	Laravel\Lumen\Routing\Controller,
	Illuminate\Http\Request;

use Laravel\Lumen\Routing\Controller as BaseController,
    Validator;

use
	App\Models\UploadScenario as USM,
	App\Traits\Session,
	Laravel\Lumen\Routing\Controller,
	Illuminate\Http\Request;


use App\Http\Controllers\Controller,
    Illuminate\Http\Request;

use Laravel\Lumen\Routing\Controller,
    App\Models\User\CloudCollectModel,
    Validator,
    Illuminate\Http\Request;

use App\Traits\Client;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller,
    App\Http\Controllers\Payment\Payment,
    App\Models\User\CloudLog,
    Illuminate\Support\Facades\Log,
    Illuminate\Http\Request,
    App\Models\User\CloudCollectModel,
    Illuminate\Support\Facades\Validator,
    App\Traits\Tool;

use
    Illuminate\Http\Request,
    App\Http\Controllers\System\Message,
    Laravel\Lumen\Routing\Controller as Base;

use App\Http\Controllers\Payment\Payment, Alipay, Wxpay;
use App\Traits\{Client, Tool};
use App\Models\User\{User, HcmcoinLog, PaymentLog, Log};

use
    App\Http\Controllers\Payment\Payment,
    App\Http\Controllers\Payment\Alipay, Wxpay,
    Illuminate\Support\Facades\Validator;

use App\Models\User\{User, PaymentLog, Log};
use App\Traits\{Client, Tool};


use App\Traits\Client;
use App\Models\User\{User, Log as ULog};
use App\Models\Withdraw\{Account as WAccount, Log as WLog};
use App\Models\Binding\{Binding, Attr as BAttr, Value as BValue};

use Illuminate\Http\Request,
    App\Models\AuthModel;
use Illuminate\Support\Facades\Redis;

use App\Models\AuthModel,
    Illuminate\Http\Request,
    App\Models\ToolsModel,
    App\Models\User\UserCashModel,
    Illuminate\Support\Facades\Redis,
    Maatwebsite\Excel\Facades\Excel,
    Illuminate\Support\Facades\DB;

use
    App\Traits\Session,
    Closure,
    Firebase\JWT\JWT as FirebaseJWT;

use
    App\Traits\Tool,
    Closure;

use
    Closure,
    App\Traits\Tool,
    Firebase\JWT\JWT as FirebaseJWT;

use Closure,
    Firebase\JWT\JWT,
    Firebase\JWT\SignatureInvalidException;

==============构造方法类======================
public function __construct(Request $req)
{
	$this->req = $req;
}



==============特殊方法类==================
public function offsetSet($offset, $value): void {}

public function msg($lang): self {}

protected function getDefaultLang(): string {}

public function ($user_id = null) use ($log_id, $params) :bool {}


==============验证方法=====================
$this->validate($this->req, [
   'name' => 'required',
   'pswd' => 'required',
]);


$rules = [
    'order_id' => 'required|numeric|min:1',
    'addr' => 'required|string|max:50',
    'house' => 'required|string|max:50',
    'intro' => 'nullable|max:100'
];
if (!$this->verifyUserParams($params,$rules) || $params['id'] <= 0) {
    return response()->json([
        'err' => 2011,
        'msg' => '参数错误',
        'dat' => ''
    ]);
}

$validator = Validator::make($params, $rules);

$validator = Validator::make($params, [
    'mobile' => [
        'required',
        'numeric',
        'regex:/^1[3|4|5|7|8][0-9]{9}$/'
    ],
    'title' => 'required|max:30'
]);
if ($validator->fails()) {
    return response()->json([
        'err' => 403,
        'msg' => $validator->errors()->first()
    ]);
}

if (!app('alipay.mobile')->verify()) {
    return 'fail';
}


==============获取参数=====================
$this->auth = $ssn->get('admin');

$uploadScenarios = USM::all();	// use App\Models\UploadScenario as USM

$params = $request->all();

$params['agent_id'] = $request->get('agent_id');

$params['ip'] = $request->ip();

$payMethod = $this->req->get('payment');

$this->sys_msg['HCMCOIN_TOPUP'],

$user = User::find($this->user_id);

$user = $user->find($this->user_id) ?? [];


==============数据返回=====================
return response()->json($fields, 200);

return view('admin.login');

return view('admin.dd', compact('tbNames', 'tables'));

return redirect()->route('admin_dashboard');

return redirect()->to('/sys/upload_scenario/'.$us_id);

return response()->json([
    'err' => $err,
    'msg' => $msg,
    'dat' => $dat
]);

return $this->responseJson(0,'请求成功',$data);

return response()->json([
    'errcode' => 0,
    'msg' => '登陆商家系统成功',
    'data' => [
        'url' => $url
    ]
])
->withCookie(new \Symfony\Component\HttpFoundation\Cookie('BIZ_TOKEN', 'token', time() - 36000))
->withCookie(new \Symfony\Component\HttpFoundation\Cookie('BIZ_TOKEN', $shop_token, time() + 7200));


return resource_path().'/sys_msg/'.$this->lang;

return isset($this->text[$offset]);

return $this->text = $dat;

==============操作模型=====================
$admin = \App\Models\Admin::where('admin_name', $this->req->name)
->where('passwd', md5($this->req->pswd))
->where('role_id', 1)
->where('closed', 0)
->first();


$model = new UserCashModel;
$data = $model->completeCashData($params);

$log_id = CloudLog::insertGetId([
    'from_mobile' => $current_userinfo->mobile,
    'go_mobile' => $params['gather_mobile'],
    'money' => $params['amount'],
    'trade_no' => $trade_no,
    'reg_at' => time()
]);

$affect_rows = DB::table('jh_member')
    ->where('uid', $params['user_id'])
    ->decrement('hcmcoin', $params['amount']);


$res = $user
		->select('hcmcoin')
		->whereUid($this->user_id)
		->get()        // Collection
		->toArray();   // Array

$paymentLoggedId = PaymentLog::insertGetId([
   'uid'  => $this->user_id,
   'from' => 'hcmcoin_topup',
   'payment'  => 'wxpay',
   'trade_no' => $params['trade_no'],
   'amount'   => $params['amount'],
   'payed'    => 0,
   'clientip' => $params['client_ip'],
   'dateline' => $timestamp,
]);

$data = DB::table('jh_finance as a')
    ->select(DB::raw('a.staff_id, b.name, SUM(a.staff_amount) as staff_amount, b.account_type, b.account_number'))
    ->leftjoin('jh_staff as b','a.staff_id','=','b.staff_id')
    ->where('a.staff_status', '=', '0')
    ->whereIn('a.staff_id',$staff_id)
    ->orderby('a.finance_id','desc')
    ->groupBy('a.staff_id')
    ->get();

==============SESSION======================
$this->auth = $ssn->get('admin');

$ssn->set('admin', $admin);

$ssn->destory();

$noticeMsg = $ssn->flush('deletion_notice_msg');

public function logout(Session $ssn)
{
	$ssn->destory();
	return redirect()->route('admin_login');
}


==============使用函数======================
array_unique(array)

compact(varname)

is_object(var)

fopen(filename, mode)

fwrite(handle, string)

json_encode(value)

fclose(handle)

session_start(oid)

implode(glue, pieces)

in_array(needle, haystack)

file_exists(filename)

array_merge(array1)

strlen(string)

preg_match(pattern, subject)

file_get_contents(filename)

date('Ymd',time())

date('Y-m-d H:i:s', $timestamp)

array_unique(array)

substr(string, start)

mt_rand(oid)

iconv(in_charset, out_charset, str)		// iconv — 字符串按要求的字符编码来转换
string iconv ( string $in_charset , string $out_charset , string $str ) // 返回转换后的字符串， 或者在失败时返回 FALSE
 $this->_smsConfig['sendcontent'] = iconv('UTF-8', 'GB2312//IGNORE', "您的短信验证码是{$code}，该验证码3分钟有效。【惠吃猫】");
		 in_charset
		输入的字符集。

		out_charset
		输出的字符集。

		str
		要转换的字符串。

strtotime(time)

array_unshift(array, var)



==============事务相关======================
switch ($this->_req->get('trade_status')) {
    case 'TRADE_SUCCESS':
    case 'TRADE_FINISHED':
        DB::beginTransaction();		// 开启事务
        try {
            //TODO: 支付成功，取得订单号进行其它相关操作。
            $current_time = time();
            $out_trade_no = $this->_req->get('out_trade_no', 0);

            $stream_info = DB::table('hcm_member_cloud_collect_stream')
                ->select(['go_mobile', 'money'])
                ->where('trade_no', $out_trade_no)
                ->first();

            if (empty($stream_info)) {
                throw new \Exception('查询不到支付宝回调的订单信息');
            }

            $user_money = $stream_info->money - ($stream_info->money * 0.16);

            CloudLog::where('trade_no', $out_trade_no)
                ->update([
                    'pay_status' => 1,
                    'pay_time' => $current_time,
                    'pay_type' => 4
                ]);

            DB::table('jh_member')->where('mobile', $stream_info->go_mobile)->increment('money', $user_money);

            $go_info = DB::table('jh_member')->select(['uid'])->where('mobile',$stream_info->go_mobile)->first();

            DB::table('jh_member_log')
                ->insertGetId([
                    'uid' => $go_info->uid,
                    'type' =>  'money',
                    'number' => "+{$user_money}",
                    'intro' => "云联收款",
                    'day'   => date('Ymd',time()),
                    'dateline' => time()
                ]);

            DB::commit();		// 成功则提交事务
        } catch (\Exception $e) {
            DB::rollback();		// 失败则回滚事务
            Log::debug($e->getMessage(), [
                'out_trade_no' => $this->_req->get('out_trade_no'),
                'trade_no' => $this->_req->get('trade_no')
            ]);
            return 'fail';
        }
        break;
    default:
        return 'fail';
        break;
}



\DB::beginTransaction();

$timestamp = time();
// Execute transaction hook
$transHookSuccess = $this->bevipTranshook()(
    $user,
    $paymentLog->amount,
    $payment,
    $paymentLog->clientip,
    $timestamp
);

if ($transHookSuccess) {
    $outTradeNoMap = [
        'alipay' => $this->req->get('trade_no'),
        'wxpay'  => $this->req->get('transaction_id'),
    ];
    $outTradeNo = $outTradeNoMap[$payment] ?? 'unknown';
    // Update payment log
    $updatedPayStatus = PaymentLog::whereLogId(
        $paymentLog->log_id
    )
    ->update([
        'payed'   => 1,
        'payedip' => $paymentLog->clientip,
        'pay_trade_no' => $outTradeNo,
    ]);

    if ($updatedPayStatus >= 0) {
        \DB::commit();
        return 'success';
    }

    \DB::rollback();
}


==============抛异常=======================
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





==============错误提示======================
Log::notice('系统无法插入hcm_member_collect_stream表', [
    'data' => [
        'from_mobile' => $current_userinfo->mobile,
        'go_mobile' => $params['gather_mobile'],
        'reg_at' => time()
    ]
]);



==============Redis=========================
$redis_data = Redis::get("code_{$params['uc_num']}");

 if (Redis::get($request->ip()) >= 10) {
            return $this->responseJson(1094,'验证码以超过限制');
        }

Redis::incr($request->ip());
Redis::set($redis_key,json_encode(['code'=>$code,'timestamp'=>time()]));
Redis::expire($redis_key,180);


==============Excel=========================
Excel::create($title, function($excel) use($data) {
    $excel->sheet('Sheet1', function($sheet) use($data) {
        $sheet->fromArray($data);
    });
})->export('xls',[
    'Access-Control-Allow-Origin'  => '*',
    'Access-Control-Allow-Methods' => '*',
    'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, AUTHORIZATION',
    'Access-Control-Max-Age'       => 86400,
]);

==============其他==========================
env('APP_DEBUG') ?? false

$trade_no = Tool::tradeNo($this->_userinfo['uid']);

$_SERVER['HTTPS']

$_SERVER['HTTP_X_FORWARDED_PROTO']

$clientIP = $clientIP  ?? Client::ip();

$result = $tools->http('http://jiekou.56dxw.com/sms/HttpInterface.aspx',$this->_smsConfig,'POST');




==============responseJson=================
protected function responseJson($status,$msg,$data=[])
{
    if (!is_numeric($status) || !is_string($msg)) {
        throw new \InvalidArgumentException('类型错误');
    }

    if (!empty($data)) {
        $array = [
            'errcode' => $status,
            'msg' => $msg,
            'data' => $data
        ];
    } else {
        $array = [
            'errcode' => $status,
            'msg' => $msg
        ];
    }
    return response()->json($array);
}





============verifyUserParams================
protected function verifyUserParams($params,$rules)
{
    if (!is_array($params)
        || empty($params)
        || !is_array($rules)
        || empty($rules)) {
        return false;
    }

    $validator = Validator::make($params, $rules);

    if ($validator->fails()) {
        $this->_msg = $validator->errors()->first();
        return false;
    }

    return true;
}


=================console=====================
function console($data,$flag=true){
    $stdout = fopen('php://stdout', 'w');
    if ($flag) {
        fwrite($stdout,json_encode($data)."\n");
    } else {
        fwrite($stdout,$data."\n");
    }
    fclose($stdout);
}


====================JWT=====================
//token
$token = [
    "iss" => $_SERVER['SERVER_NAME'],
    "sub" => $data->proxy_id,
    "name" => $data->name,
];

//有效期
JWT::$leeway = 3600 * 5;

$jwt = JWT::encode($token, env('SERECT_KEY'),['HS256']);

$data->token = $jwt;

return $jwt;