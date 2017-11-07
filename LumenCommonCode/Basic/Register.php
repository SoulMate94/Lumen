<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\Models\RegisterModel;

class Register extends Controller
{
    public function register(Request $req)
    {
        $params = $req->all();

        $rules = [
            'name'  =>  'required|string|max:20',
            'shop_name' => [
                'required',
                'numeric',
                'regex:/^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/'
            ],
            'remark' => 'required|string|max:50'
        ];

        if (!$this->verifyUserParams($params, $rules)) {
            return $this->responJson(1800, $this->_msg);
        }

        $register = new RegisterModel;
        $affect_row = $register->registerShop($params);

        return $affect_row
        ? $this->responJson(0, '申请入驻成功')
        : $this->responseJson(2000,'该手机号已经注册或系统发生未知错误');
    }
}