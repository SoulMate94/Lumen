<?php

==========app\Http\Controllers\ToolsController.php=============================
<?php

namespace App\Http\Controllers;

use
    App\Models\AuthModel,
    App\Models\Finance\FinanceModel,
    Illuminate\Http\Request,
    App\Models\ToolsModel,
    App\Models\User\UserCashModel,
    Illuminate\Support\Facades\Redis,
    Maatwebsit\EXcel\Facades\Excel,
    Illuminate\Support\Facades\DB;

class ToolsController extends Controller
{
    private $_smsConfig = [
        'comid'     =>  '3192',
        'smsnumber' =>  '10690',
        'username'  =>  'coderd',
        'userpwd'   =>  '8mr0yhrl',
        'sendtime'  =>  ''
    ];

    public function sendMessage(Request $req)
    {
        $params = $req->all();

        $rules = [
            'uc_num'=>[
                'required',
                'numeric',
                'regex:/^1[3|4|5|7|8][0-9]{9}$/'
            ],
            'flag'=>'required|in:0,1'
        ];

        if (! $this->verifyUserParams($params, $rules)) {
            return $this->responseJson(1800, $this->_msg);
        }

        $auth = new AuthModel();

        $boolean_data = $auth->checkUserMobileExist($params['uc_num']);

        if (empty($boolean_data)) {
            return $this->responseJson(1903, '账号未注册或账号未激活');
        }

        $this->_smsConfig['handtel'] = $params['uc_num'];

        $code = '';

        for ($i=0; $i < 6; $i++) {
            $code .= substr('0123456789', mt_rand(0,9), 1);
        }

        $this->_smsConfig['sendcontent'] = iconv('UTF-8', 'GB2312//IGNORE', "您的短信验证码是{$code}，该验证码3分钟有效。【惠吃猫】");

        $redis_key = $params['flag']
        ? "code_{$params['uc_num']}"
        : "code_revise_{$params['uc_num']}";

        if (Redis::get($req->ip()) >= 10) {
            return $this->responseJson(1094, '验证码已超过限制');
        }

        Redis::incr($req->ip());
        Redis::set($redis_key, json_encode(['code'=>$code,'timestamp'=>time()]));
        Redis::expire($redis_key,180);

        $tools = new ToolsModel;

        // Send
        $res = $tools->http('http://jiekou.56dxw.com/sms/HttpInterface.aspx',
            $this->_smsConfig, 'POST'
        );

        if ($res) {
            return $this->responseJson(0, '发送成功');
        }

        return $this->responseJson(2000, $this->_msg);
    }


    public function downloadExcel(Request $req)
    {
        $params = $req->all();

        $params['agent_id'] = $req->get('agent_id', 0);

        if (!isset($params['act']) ||
            is_numeric($params['act']) ||
            $params['agent_id'] <= 0
        ) {
            return $this->responseJson(2000, '请选择想导出的模块');
        }

        // 统一将对象转化为数组
        $data = json_decode(json_encode($data), true);
        // 将Excel头部插入标题
        if (isset($title_array) && $title_array) {
            array_unshift($data, $title_array);
        }

        if ($params['act'] == 'finance') {
            Excel::create($title, function($excel) use($data,$data2) {
                $excel->sheet('结算汇总', function($sheet) use($data) {
                    $sheet->fromArray($data);
                });
                $excel->sheet('结算详情', function($sheet) use($data2) {
                    $sheet->fromArray($data2);
                });
            })->export('xls', [
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Methods' => '*',
                'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, AUTHORIZATION',
                'Access-Control-Max-Age'       => 86400,
            ]);
        } else {
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
        }
    }
}