<?php

#命名空间
namespace App\Http\Controllers\Finance\User;

#引入
use App\Http\Controllers\Controller,
    Illuminate\Http\Request,
    App\Models\User\UserCashModel;



#获取所有
$params = $request->all();

#获取指定值
$data = $model->userCashData($request->get('agent_id'), $params);

$user = User::find($params['user_id']);

#过滤条件
$rules = [
    'id' => 'required'
];

#实例化模型
$model = new UserCashModel;


#返回JSON格式的值
return response()->json([
    'err' => 0,
    'msg' => 'ok',
    'dat' => $data
]);

return $this->responseJson(0,'请求成功',$data);

#验证条件
use Illuminate\Support\Facades\Validator;
$validator = Validator::make($params, [
    'client' => 'required|in:web,wap,mobile',
    'amount' => 'required|numeric|min:0.01',
    'notify' => 'required|url',
    'trade_no' => 'required',
    'desc'     => 'required',
]);

或者

$rules = [
    'payment' => 'required|in:money,alipay,kuaiqian,wxpay,hcmcoin',
    'client' => 'required|in:mobile,web',
    'amount' => 'required|numeric|min:1',
    'transfer_mobile' => [
        'required',
        'numeric',
        'regex:/^1[3|4|5|7|8][0-9]{9}$/'
    ],
    'gather_mobile' => [
        'required',
        'numeric',
        'regex:/^1[3|4|5|7|8][0-9]{9}$/'
    ]
]

$validator = Validator::make($params, $rules);

或者
$this->validate($this->req, [
            'payment' => 'required|in:money,alipay,kuaiqian,wxpay',
            'amount'  => 'required|numeric|min:10',
        ]);

#验证失败返回
if ($validator->fails()) {
    return [
        'err' => 400,
        'msg' => $validator->errors()->first(),
    ];
}

#判断方法是否存在
if (! method_exists($this, $fillPayDataHandler)) {
    return [
        'err' => 5001,
        'msg' => 'Missing payment parameters handler.',
    ];
}

#引入多个模型
use App\Models\User\{User, Log, PaymentLog};

use App\Models\User\{User, Log as ULog};
use App\Models\Withdraw\{Account as WAccount, Log as WLog};
use App\Models\Binding\{Binding, Attr as BAttr, Value as BValue};


#获取模型数据
$payments = \DB::table('jh_payment')
->select('payment')
->get()
->toArray();

#三目运算
$class = $class ?? $this->method;

#事务相关
#开启事务
\DB::beginTransaction();

#提交
\DB::commit();

#回滚
\DB::rollback();

##事务流程
		\DB::beginTransaction();
        // Decrease user hcmcoin
        // Increase user balance deducted with redeem fee
        $user->hcmcoin = $hcmcoinDecreasedNum;
        $user->money  += $amount * ($this->getRedeemFee());
        $updateSuccess = $user->save();

        // Log redeem event into database
        $loggedId = HcmcoinLog::insertGetId([
            'mid'    => $this->user_id,
            'mtype'  => 'user',
            'amount' => -$amount,
            'event'  => 'redeem',
            'desc'   => '用户用猫豆兑换余额',
            'ipv4'   => Client::ip(),
            'create_at' => date('Y-m-d H:i:s'),
        ]);

        if ($updateSuccess && $loggedId) {
            \DB::commit();
            $err = 0;
            $msg = 'ok';
        } else {
            \DB::rollback();
            $err = 5004;
            $this->errMsg('REDEEM_FAILED');
        }

        return response()->json([
            'err' => $err,
            'msg' => $msg,
        ]);
#抛异常
if (! preg_match('/^1[34578]\d{9}$/u', $mobile)) {
    throw new \Exception(
        'Illegal mobile number format.'
    );
}

#获取ENV参数
$this->key = env('AMAP_KEY');

#获取流水号
$trade_no = Tool::tradeNo(1);


#日志/提示
Log::notice('系统无法插入hcm_member_collect_stream表', [
    'data' => [
        'from_mobile' => $params['transfer_mobile'],
        'go_mobile' => $params['gather_mobile'],
        'reg_at' => time()
    ]
]);


// 验证请求。
if (!app('alipay.mobile')->verify()) {
    return 'fail';
}

#标准查询方法
    public function query(User $user)
    {
        $res = $user
        ->select('hcmcoin')
        ->whereUid($this->user_id)
        ->get();

        $err = $res ? 0 : 404;
        $msg = $res ? 'ok' : 'no result for `'.$this->user_id.'`.';
        $dat = $res ?? [];

        return response()->json([
            'err' => $err,
            'msg' => $msg,
            'dat' => $dat,
        ]);
    }

#标准数据库查询
    $logs = $log
    ->select('amount', 'desc', 'create_at')
    ->whereMtypeAndMid('user', $this->user_id)
    ->skip($start)
    ->take($scale)
    ->orderBy('create_at', 'desc')
    ->get()
    ->toArray();    


#Redis
$redis_data = Redis::get("code_{$params['uc_num']}");

    Redis::incr($request->ip());
    Redis::set($redis_key,json_encode(['code'=>$code,'timestamp'=>time()]));
    Redis::expire($redis_key,180);



#标准数据验证    
    $params = $request->all();

    $rules = [

        'uc_num'=> [
            'required',
            'numeric',
            'regex:/^1[3|4|5|7|8][0-9]{9}$/'
        ],
        'code' => 'required|min:6|max:6',
        'uc_pass' => 'required|min:8|max:16|confirmed',
    ];

    if (!$this->verifyUserParams($params,$rules)) {
        return $this->responseJson(1800,$this->_msg);
    }

#控制台函数调试
function console($data,$flag=true){
    $stdout = fopen('php://stdout', 'w');
    if ($flag) {
        fwrite($stdout,json_encode($data)."\n");
    } else {
        fwrite($stdout,$data."\n");
    }
    fclose($stdout);
}    


#JWT  
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


#下载Excel
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


#新增属性
$request->attributes->add([
    'jwt_auth' => $this->auth,
]);    

#获取被保护变量
protected $auth = false;
    public function __construct()
    {
        $this->auth = $this->authorise();
    }
    if (false === $this->auth) {
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }


#反解token
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


#头文件
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

#判断方法
if ('OPTIONS' == $request->getMethod()) {
	return response(null, 200, $headers);
}

#返回带请求头的文件
return $next($request)->withHeaders($headers);


#Jobs/job.php
#Jobs目录是放置队列任务的地方，应用中的任务可以被队列化，也可以在当前请求生命周期内同步执行
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class Job implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue, Queueable, SerializesModels;
}


##Models
#命名空间
namespace App\Models;

#引入类
use Illuminate\Database\Eloquent\Model;

#表以及主键
protected $table = 'jh_agent';
protected $primaryKey = 'agent_id';

#文件命名
Models/Agent/Agent.php
class Agent extends Model
{
    protected $table = 'jh_agent';
    protected $primaryKey = 'agent_id';

    public $timestamps = false;
}


#一对多  模型关联
public function binding()
{
    return $this->belongsTo(Binding::class, 'bind_id', 'id');
}


#hasMany
public function attrs()
{
    return $this->hasMany(Attr::class, 'bind_id', 'id');
}


#商家搜索

if( isset($params['name']) && $params['name'] ){
    $shop_id = DB::table('jh_shop')
        ->where(
            [
                ['agent_id', '=', $uid],
                ['audit', '=', 1],
                ['title', 'like', '%'.$params['name'].'%'],
            ]
        )
        ->pluck('shop_id');
    
    $shop_id = json_decode(json_encode($shop_id),true);
    $datas['params']['shop_name'] = $params['name'];
}else{
    $shop_id = [];
    $datas['params']['shop_name'] = '';
}


#链表查询 分页
use Illuminate\Support\Facades\DB;      //引入DB类
protected $_perpage = 20;  
$data = DB::table('jh_order as a')
    ->select('a.order_id','a.shop_id','a.amount','a.pay_time','a.pay_code','a.pei_type','b.service_charge','b.title as shop_name','c.trade_no','c.pay_trade_no')
    ->leftjoin('jh_shop as b','a.shop_id','=','b.shop_id')
    ->leftjoin('jh_payment_log as c','a.order_id','=','c.order_id')
    ->where(
        function ($query)  use ($where,$shop_id) {
            $query->where($where);
            if($shop_id){
                $query->whereIn('a.shop_id', $shop_id);
            }
        }
    )
    ->whereBetWeen('a.day',[date('Ymd',$startTime), date('Ymd',$endTime)])
    ->orderby('a.order_id','desc')
    ->paginate($this->_perpage);

//开启事务
DB::beginTransaction();     //开启事务
try {
    DB::table('jh_order')->where(['order_id' => $params['order_id'],'agent_id' => $params['agent_id']])->update($_array);
    DB::table('jh_order_log')->insertGetId($insert_log_array);
    DB::commit();           //提交事务
    return true;
} catch (\PDOException $ex) {
    echo $ex->getMessage();
    exit;
    DB::rollback();         //回滚事务
    return false;
}    



##增删改查

##增
$insert_id = DB::table('jh_waimai_product')
            ->insertGetId([
                'shop_id' => $params['shop_id'],
                'ispay' => $params['ispay'],
                'cate_id' => $params['cate_id'],
                'title' => $params['title'],
                'price' => $params['price'],
                'package_price' => $params['package_price'],
                'sales' => $params['sales'],
                'stock' => $params['stock'],
                'intro' => $params['intro'],
                'orderby' => $params['orderby'],
                'dateline' => time()
            ]);

        return $insert_id;

##改
$affect_rows  = DB::table('jh_waimai_product')
    ->where(['product_id' => $params['product_id'], 'shop_id' => $params['shop_id']])
    ->update([
        'shop_id' => $params['shop_id'],
        'ispay' => $params['ispay'],
        'cate_id' => $params['cate_id'],
        'title' => $params['title'],
        'price' => $params['price'],
        'package_price' => $params['package_price'],
        'sales' => $params['sales'],
        'stock' => $params['stock'],
        'intro' => $params['intro'],
        'orderby' => $params['orderby']
    ]);

return $affect_rows;        

##删
$affect_rows = DB::table('jh_waimai_product')
    ->where(['product_id' => $product_id])
    ->update([
        'closed' => 1
    ]);

return $affect_rows;

##查
$data = DB::table('jh_waimai_product as a')
    ->select('a.shop_id','a.ispay','a.title','a.price','a.stock','a.package_price','a.cate_id','a.sales','a.dateline','a.intro','a.orderby','a.closed','a.photo','b.title as cate_title','a.clientip','c.title as shop_title')
    ->leftjoin('jh_waimai_product_cate as b','a.cate_id','=','b.cate_id')
    ->leftjoin('jh_shop as c','a.shop_id','=','c.shop_id')
    ->where('a.product_id',$product_id)
    ->where('a.closed',0)
    ->first();

return $data;


#json_encode/json_decode
$write_range = json_decode($params['freight_write_range'],true);
assoc 当该参数为 TRUE 时，将返回 array 而非 object 。


#验证手机号
if (
    empty($mobile) ||
    strlen($mobile) != 11 ||
    !preg_match('/^1[3|4|5|7|8][0-9]{9}$/',$mobile)
) {
    return [];
}


#模型--白名单--隐藏
protected $guarded = [
        'uid',
];

protected $visible = [
    'uid'
];

protected $hidden = [
    'passwd',
    // 'paypasswd',
];


#密码验证
if ($data->pswd != hash_hmac('sha256',$params['uc_pass'],env('PASSWORD_KEY'))) {
    return false;
}
unset($data->pswd);

#修改密码
$affect_rows = DB::table($this->_table)
->where(['mobile'=>$params['uc_num']])
->update([
    'pswd' => hash_hmac('sha256',$params['uc_pass'],env('PASSWORD_KEY'))
]);


// 检查是否有错误发生
if(!curl_errno($http)) {
    $info = curl_getinfo($http);
}


#服务提供者
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

#此方法会在所有其它的服务提供者被注册后才被调用
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
public function boot()
{
    $this->app['auth']->viaRequest('api', function ($request) {
        if ($request->input('api_token')) {
            return User::where('api_token', $request->input('api_token'))->first();
        }
    });
}

#得到单一实例
$this->app->singleton('command.patch.coord-convert', function () {
    return new \App\Console\Commands\CoordConvert;
});


#服务提供
protected $listen = [
    'App\Events\SomeEvent' => [
        'App\Listeners\EventListener',
    ],
];


#config  --Redis
'redis' => [

    'cluster' => env('REDIS_CLUSTER', false),

    'default' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'port'     => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DATABASE', 0),
        'password' => env('REDIS_PASSWORD', null),
    ],

],

#标准路由
$app->group([
    'prefix'     => 'sys',          //前缀
    'namespace'  => 'Admin',        //命名空间
], function () use ($app) {
    $app->group([
        'middleware' => [           //中间件
            'admin_auth',
        ],
    ], function () use ($app) {
        $app->get('/', [            //Get请求
            'as'   => 'admin_dashboard',
            'uses' => 'Admin@dashboard',
        ]);
        $app->get('dd', 'DataDict@index');
        $app->get('dd/fields', 'DataDict@getFields');
        $app->post('logout', 'Passport@logout');        //Post请求
        $app->group([                           //路由组
            'prefix' => 'upload_scenario',
        ], function () use ($app) {
            $app->get('/', 'UploadScenario@index');
            $app->get('table_fields/{tbName}', 'UploadScenario@getFieldsOfTable');
            $app->get('{us_id}', 'UploadScenario@createOrEdit');
            $app->post('{us_id}', 'UploadScenario@sideReq');
        });
    });
    $app->get('login', [
        'as'   => 'admin_login',        //路由别名
        'uses' => 'Passport@login',
    ]);
    $app->post('login', 'Passport@loginAction');
});