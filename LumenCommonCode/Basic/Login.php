<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\Models\AuthModel;

class Login extends Controller
{
    public function shopLogin(Request $req)
    {
        $params = $req->all();

        $rules = [
            'shop_num' => [
                'required',
                'numeric',
                'regex:/^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/'
            ],
            'shop_passwd' => 'required|min:8|max:16'
        ];

        if (! $this->verifyUserParams($params, $rules)) {
            return $this->responseJson(1800, '参数错误');
        }

        $auth = new AuthModel;

        $data = $auth->userLoginAndCreateJwt($params);

        // token
        $token = [
            'iss' => $_SERVER['SERVER_NAME'],
            'sub' => $data->proxy_id,
            'name'=> $data->name,
        ];

        // expire
        JWT::$leeway = 3600 * 5;

        // jwt
        $jwt = JWT::encode($token, env('SERECT_KEY'),['HS256']);

        $data->token = $jwt;

        return $jwt;
    }
}