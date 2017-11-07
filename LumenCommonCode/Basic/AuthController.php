<?php

namepace App\Http\Controllers;

use Illuminate\Http\Request,
    App\Models\AuthModel;
use Illuminate\Support\Facades\Redis;

class AuthController extends Controller
{
    public function LoginByMobile(Requset $req)
    {
        $params = $req->all();
        $rules = [
            'shop_num' => [
                'required',
                'numeric',
                'regex:/^1[3|4|5|7|8][0-9]{9}$/'
            ],
            'shop_passwd' => ['required|min:8|max:16']
        ];

        if (!$this->verifyUserParams($params,$rules)) {
            return $this->responseJson(1800, $this->_msg);
        }

        $auth = new AuthModel;

        $data = $auth->userLoginAndCreateJwt($params);

        if (empty($data)) {
            return $this->responseJson(1090, '账号或密码不能为空');
        }

        return $this->responseJson(0, '登陆成功', $data);
    }

    public function LoginByCode(Requset $req)
    {
        $params = $req->all();
        $rules  = [
            'shop_num' => [
                'required',
                'numeric',
                'regex:/^1[3|4|5|7|8][0-9]{9}$/'
            ],
            'code' => 'required|min:6|max:6'
        ];

        if (!$this->verifyUserParams($params, $rules)) {
            return $this->responseJson(1800, $this->_msg);
        }

        $redis_data = Redis::get("code_{$params['shop_num']}");

        if (empty($redis_data)) {
            return $this->responseJson(1902, '非法验证码');
        } else {
            $redis_data = json_decode($redis_data);
        }

        if ($redis_data->code !== $params['code']) {
            return $this->responseJson(1903, '验证码错误');
        }

        $auth = new AuthModel;

        $data = $auth->userLoginAndCreateJwt($params, false);

        if (empty($data)) {
            return $this->responseJson(1090, '账号不存在或者密码错误');
        }

        return $this->responseJson(0, '登陆成功', $data);
    }

    public function shopRevisePasswd(Request $req)
    {
        $params = $req->all();
        $rules  =  [
            'shop_num' => [
                'required',
                'numeric',
                'regex:/^1[3|4|5|7|8][0-9]{9}$/'
            ],
            'code' => 'required|min:6|max:6',
            'shop_passwd' => 'required|min:8|max:16|confirmed',
        ];

        if (!$this->verifyUserParams($params, $rules)) {
            return $this->responseJson(1800, $this->_msg);
        }

        $redis_data = Redis::get("code_revise_{$params['shop_num']}");

        if (empty($redis_data)) {
            return $this->responseJson(1902, '非法验证码');
        } else {
            $redis_data = json_decode($redis_data);
        }

        if ($redis_data->code != $params['code']) {
            return $this->responseJson(1903, '验证码错误');
        }

        $AuthModel = new AuthModel;

        $affect_rows = $AuthModel->userRevisePassForDatabase($params);

        if ($affect_rows) {
            return $this->responseJson(0, '修改密码成功');
        }

        return $this->responseJson(2000, $this->_msg);
    }
}