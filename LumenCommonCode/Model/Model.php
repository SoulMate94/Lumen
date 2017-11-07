<?php

=========================命名空间=============================
namespace App\Models;


=========================类的引入=============================
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use App\Models\ToolsModel;

use Illuminate\Support\Facades\Log;
use App\Traits\Client;
use Illuminate\Support\Facades\DB;
use Mockery\CountValidator\Exception;

use Illuminate\Support\Facades\DB,
    Firebase\JWT\JWT;





=========================方法类=============================
标准格式#
class Agent extends Model
{
    protected $table = 'jh_agent';
    protected $primaryKey = 'agent_id';
    public $timestamps = false;
    protected $_perpage = 20;
}


=========================操作模型===========================
return \DB::table($this->table)
->whereBindId($bindId)
->get();

$count = DB::table('jh_order')
        ->where(
            function ($query)  use ($qxWhere,$shop_id) {
                $query->where($qxWhere);
                if($shop_id){
                    $query->whereIn('shop_id', $shop_id);
                }
            }
        )
        ->whereBetWeen('day',[date('Ymd',$startTime), date('Ymd',$endTime)])
        ->count();

$orders = DB::table('jh_order')
        ->select(DB::raw('SUM(amount) as day_amount, day, COUNT(1) as day_order'))
        // ->where($charWhere)
        ->where(
            function ($query)  use ($charWhere,$shop_id) {
                $query->where($charWhere);
                if($shop_id){
                    $query->whereIn('shop_id', $shop_id);
                }
            }
        )
        ->whereBetween('day',[date('Ymd',$startTime), date('Ymd',$endTime)])
        ->groupBy('day')
        ->get();

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


$pro_name = DB::table('jh_waimai_order_product')
        ->where(['order_id' => $value->order_id])
        ->pluck('product_name');


$where = [
    ['a.agent_id', '=', $uid],
    ['a.shop_status', '=', $params['status']],
];

$data = DB::table('jh_finance as a')
    ->select(DB::raw('SUM(a.total_amount) as total_amount, SUM(a.shop_amount) as shop_amount, a.shop_id, a.service_charge, b.addr, b.title, c.account_type, c.account_number'))
    ->leftjoin('jh_shop as b','a.shop_id','=','b.shop_id')
    ->leftjoin('jh_shop_account as c','a.shop_id','=','c.shop_id')
    ->where($where)
    ->groupBy('a.shop_id')
    ->paginate($this->_perpage);

$affect_rows  = DB::table('jh_finance')
    ->where(['shop_status' => 2, 'agent_id' => $uid])
    ->whereIn('shop_id', $shop_id)
    ->update([
        'shop_status' => 1,
        'shop_time' => time(),
    ]);


$where = [
    ['agent_id', '=', $agent_id],
    ['audit', '=', 1],
    ['title', 'like', '%'.$params['name'].'%'],
];
$data = DB::table('jh_shop')
    ->where($where)
    ->pluck('title');

$affect_rows = DB::table('jh_order_complaint')
    ->where(['agent_id' => $params['agent_id']])
    ->whereIn('complaint_id',explode(',',$params['complaint_id']))
    ->update(['closed' => 1]);

$affect_rows = DB::table('jh_order_complaint')
    ->where(['agent_id' => $params['agent_id'],'complaint_id' => $params['complaint_id']])
    ->update(['reply' => $params['reply'],'reply_time' => time()]);



$data = DB::table('jh_order as a')
            ->select('a.order_id','a.total_price','a.amount','a.money','a.hongbao','a.pei_type','a.order_status','a.online_pay','a.pay_status','a.dateline','b.title')
            ->leftjoin('jh_shop as b','a.shop_id','=','b.shop_id')
            ->where(function($query) use ($params){

                $query->where('a.staff_id',0);
                $query->where('a.pay_status',1);
                $query->where('a.order_status',0);
                $query->where('a.closed',0);
                $query->whereBetween('a.pei_type',[1,2]);

                if (isset($params['order_id']) && is_numeric($params['order_id']) && $params['order_id'] > 0) {
                    $query->where('a.order_id',$params['order_id']);
                }

                if (isset($params['shop_id']) && is_numeric($params['shop_id']) && $params['shop_id'] > 0) {
                    $query->where('a.shop_id',$params['shop_id']);
                }

                if (isset($params['status']) && in_array($params['status'],[0,1,2,3])) {
                    switch ($params['status']) {
                        case 1:
                            $query->where('a.order_status',0);
                            break;

                        case 2:
                            $query->whereBetween('a.order_status',[1,2,3,4,5]);
                            break;

                        case 3:
                            $query->where('a.order_status',8);
                            break;
                    }
                }

                //时间查询
                if (isset($params['start_time'])) {
                    $query->where('dateline','>=',strtotime($params['start_time']));
                }

                if (isset($params['end_time'])) {
                    $query->where('dateline','<=',strtotime($params['end_time'] .' + 1 day'));
                }
            })
            ->paginate($this->_perpage);

$data = DB::table('jh_staff')
    ->select(['staff_id','name','mobile','lat','lng','status'])
    ->where(['closed' => 0 ,'audit' => 1,'agent_id' => $params['agent_id']])
    ->where(function($query) use ($squares) {

        $query->whereBetween('lat',[$squares['left-bottom']['lat'],$squares['right-top']['lat']]);

        $query->whereBetween('lng',[$squares['left-bottom']['lng'],$squares['right-top']['lng']]);

    })
    ->orderby('status','desc')
    ->paginate($this->_perpage);


$data = DB::table('jh_order as a')
            ->select('a.order_id','a.total_price','a.amount','a.money','a.hongbao','a.order_status','a.online_pay','a.pay_status','a.dateline','b.title','a.house','a.contact','a.mobile','a.uid','c.name','c.mobile as staff_mobile','a.first_youhui','a.intro','a.reason','a.lasttime','a.addr','a.house')
            ->leftjoin('jh_shop as b','b.shop_id','=','a.shop_id')
            ->leftjoin('jh_staff as c','c.staff_id','=','a.staff_id')
            ->where(function($query) use ($params){
                if (isset($params['order_status']) && is_numeric($params['order_status'])) {
                    if ($params['order_status'] == 2) {
                        $query->whereBetween('a.order_status',[2,4]);
                    } else {
                        $query->where('a.order_status',$params['order_status']);
                    }
                } else {
                    $query->where('a.order_status',0);
                }
                $query->where('a.agent_id',$params['agent_id']);
                $query->where('a.pay_status',1);
                $query->where('a.closed',0);

                if (isset($params['order_id']) && is_numeric($params['order_id'])) {
                    $query->where('a.order_id',$params['order_id']);
                }

                if (isset($params['shop_id']) && is_numeric($params['shop_id'])) {
                    $query->where('a.shop_id',$params['shop_id']);
                }

                if (isset($params['start_time'])) {
                    $query->where('a.dateline','>=',strtotime($params['start_time']));
                }

                if (isset($params['end_time'])) {
                    $query->where('a.dateline','<=',strtotime($params['end_time'].'+ 1 day'));
                }
            })
            ->orderby('a.order_id','desc')
            ->paginate($this->_perpage);

$data->log = DB::table('jh_order_log')
        ->select(['log_id','log','dateline','from'])
        ->where('order_id',$params['order_id'])
        ->limit(10)
        ->get();

DB::table('jh_member')->where(['uid' => $data->uid])->decrement('orders',1);
DB::table('jh_shop')->where(['shop_id' => $data->shop_id])->decrement('orders',1);

DB::table('hcmcoin_log')->insertGetId([
    'mid' => $data->uid,
    'mtype'=> 'user',
    'amount'=> "+{$money}",
    'event'=> 'refund',
    'desc'=> '猫豆退款',
    'ipv4'=> Client::ip()
]);

$affect_rows = DB::table('jh_shop_youhui')
    ->where(['youhui_id' => $youhui_id])
    ->limit(1)
    ->delete();

$affect_rows = DB::table('jh_waimai_product')
    ->where(['product_id' => $product_id])
    ->update([
        'closed' => 1
    ]);

return \DB::table('jh_shop_account')
->select([
    'account_type',
    'account_name',
    'account_number',
])
->whereShopId($shop_id)
->get();


$staff_ids = DB::table('jh_staff')
             ->where(['agent_id' => $uid])
             ->pluck('staff_id');


$data = DB::table('jh_staff')
    ->select([
        'staff_id','name','mobile','passwd','city_id','account_type','account_name','account_number','tixian_percent','verify_name','audit'
    ])
    ->where(['agent_id' => $uid, 'staff_id' => $staff_id])
    ->first();
$data->passwd = '******';


$sql = "
        SELECT
        a.ss_id,a.sub_uid,a.sub_mobile,
        b.go_mobile,
        c.title,
        FORMAT(sum(b.money),2) as ShopIncome,
        FORMAT(sum(b.money)/100,2) as MyIncome,
        FROM_UNIXTIME(b.pay_time, '%Y-%m-%d') AS PayTime
        FROM
        shareshop AS a
        LEFT JOIN hcm_member_cloud_collect_stream AS b ON a.sub_mobile = b.go_mobile
        LEFT JOIN jh_shop AS c ON a.sub_mobile = c.mobile
        WHERE
        a.ss_id=$ss_id
        AND b.pay_status = 1
        AND b.pay_time>=$firstDay
        AND b.pay_time<=$lastDay
        GROUP BY PayTime
        LIMIT $start,$scale
        ";
$ShareShopIncome = DB::select($sql);

return \DB::select('
    SELECT
        `create_at`,
        FORMAT(`amount`/100, 2) AS `amount`,
        `status` AS `code`,
        CASE `status`
            WHEN "expired" THEN "已过期"
            WHEN "success" THEN "成功"
            WHEN "processing" THEN "处理中"
            WHEN "failed" THEN "失败"
            ELSE "审核中"
        END
        AS status
    FROM `withdraw_log`
    WHERE `mtype` = ? AND `mid` = ?
    LIMIT ?, ?
', [
    'user',
    $uid,
    $start,
    $scale
]);

=========================数据返回===========================
return array_column(json_decode(json_encode(
    $resObj->toArray()
), true), $idKey);

return $this->belongsTo(Binding::class, 'bind_id', 'id');\

return $this->hasMany(Attr::class, 'bind_id', 'id');


=========================函数使用==========================
date(format)

strtotime(time)

substr(string, start)

json_decode(json)

explode(delimiter, string)

array_key_exists(key, array)

sprintf(format)

serialize(value)

explode(delimiter, string)

hash_hmac(algo, data, key)

unset(var)

function_exists(function_name)

curl_init()

curl_setopt(ch, option, value)

http_build_query(query_data)

curl_setopt(ch, option, value)

strpos(haystack, needle)

curl_exec(ch)

curl_getinfo(ch)

curl_errno(ch)

curl_close(ch)

is_integer()

trim(str)

parse_url(url)

isset(var)



=========================事务相关==========================
//开启事务
DB::beginTransaction();
try {
    DB::table('jh_order')->where(['order_id' => $params['order_id'],'agent_id' => $params['agent_id']])->update($_array);
    DB::table('jh_order_log')->insertGetId($insert_log_array);
    DB::commit();
    return true;
} catch (\PDOException $ex) {
    echo $ex->getMessage();
    exit;
    DB::rollback();
    return false;
}


=========================其他==============================
protected $guarded = [
    'uid',
];

protected $visible = [
    'uid'
];

protected $hidden = [
    'passwd',
    'paypasswd',
];
