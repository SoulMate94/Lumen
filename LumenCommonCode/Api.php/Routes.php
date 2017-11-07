<?php

=================api.php==========================
// 直接访问出现403
$app->get('/', function () use ($app) {
    return response()->json([
        'err' => '403',
        'msg' => 'Forbidden',
    ], 403);
});


// 返回值错误信息
$app->group([
    'middleware' => [
        'ak_sk_auth',
    ],
], function () use ($app) {
    $app->get('/sys_err_msg.json', 'System\Message@get');
});


// 数据字典
$app->group([
    'prefix'     => 'sys',
    'namespace'  => 'Admin',
], function () use ($app) {
    $app->group([
        'middleware' => [
            'admin_auth',
        ],
    ], function () use ($app) {
        $app->get('/', [
            'as'   => 'admin_dashboard',
            'uses' => 'Admin@dashboard',
        ]);
        $app->get('dd', 'DataDict@index');
        $app->get('dd/fields', 'DataDict@getFields');
        $app->post('logout', 'Passport@logout');
        $app->group([
            'prefix' => 'upload_scenario',
        ], function () use ($app) {
            $app->get('/', 'UploadScenario@index');
            $app->get('table_fields/{tbName}', 'UploadScenario@getFieldsOfTable');
            $app->get('{us_id}', 'UploadScenario@createOrEdit');
            $app->post('{us_id}', 'UploadScenario@sideReq');
        });
    });
    $app->get('login', [
        'as'   => 'admin_login',
        'uses' => 'Passport@login',
    ]);
    $app->post('login', 'Passport@loginAction');
});

// http://t0.api.hcmchi.com/sys/dd


// 七牛云-对象存储
$app->group([
    'namespace' => 'ThirdParty',
], function () use ($app) {
    $app->group([
        'prefix'     => 'qiniu',
        'middleware' => [
            'qiniu_auth',
        ]
    ], function () use ($app) {
        $app->group([
            'middleware' => [
                'jwt_auth',
            ],
        ], function () use ($app) {
            $app->get('uptoken', 'Qiniu@getUploadToken');
        });

        $app->post('upload_cbk', [
            'as'   => 'qiniu_upload_callback',
            'uses' => 'Qiniu@uploadCallback',
        ]);
    });
    $app->get('amap/point_in_area', 'Amap@pointInArea');

    // 阿里云短信服务
    $app->group([
        'middleware' => [
            'ak_sk_auth',
        ],
    ], function () use ($app) {
        $app->post('aliyun/sms/send', 'AliyunSMS@send');
    });
});


$app->group([
    'prefix'    => 'user/{id}',
    'namespace' => 'User',
    'middleware' => [
        'jwt_auth',
        'migrate_user_filter',	// 用户过滤 (可以直接获取地址栏的id)
    ],
], function () use ($app) {
    $app->get('/', 'User@info');
    $app->get('spasswd', 'User@verifySecurePasswd');
    $app->post('spasswd', 'User@updateSecurePasswd');

    $app->post('bevip', 'Hcmvip@bevip');

    $app->get('withdraw_accounts', 'Withdraw@accounts');
    $app->get('withdraws', 'Withdraw@logs');
    $app->post('withdraw', 'Withdraw@request');

    $app->group([
        'prefix' => 'hcmcoin',
    ], function () use ($app) {
        $app->get('/', 'Hcmcoin@query');
        $app->get('logs', 'Hcmcoin@logs');
        $app->post('topup', 'Hcmcoin@topup');
        $app->post('redeem', 'Hcmcoin@redeem');
    });
});


// Callbacks from other services
$app->group([
    'prefix' => 'cbk',		// 前缀--回调
], function () use ($app) {
    $app->post('hcmcoin/topup/alipay/web', [				// 网页访问Alipay
        'as'   => 'alipay_web_cbk_when_topup_hcmcoin',		//别名
        'uses' => 'User\Hcmcoin@topupWebNotifyWhenAlipay',
    ]);
    $app->post('hcmcoin/topup/alipay/mobile', [				// App访问Alipay
        'as'   => 'alipay_mobile_cbk_when_topup_hcmcoin',
        'uses' => 'User\Hcmcoin@topupMobileCallbackWhenAlipay',
    ]);
    $app->post('user/bevip/alipay/web', [
        'as'   => 'alipay_web_cbk_when_user_bevip',
        'uses' => 'User\Hcmvip@bevipWebCallbackWhenAlipay',
    ]);
    $app->post('user/bevip/alipay/mobile', [
        'as'   => 'alipay_mobile_cbk_when_user_bevip',
        'uses' => 'User\Hcmvip@bevipMobileCallbackWhenAlipay',
    ]);
    $app->post('user/bevip/wxpay/mobile', [
        'as'   => 'wxpay_mobile_cbk_when_user_bevip',
        'uses' => 'User\Hcmvip@bevipMobileCallbackWhenWxpay',
    ]);
    $app->post('user/bevip/wxpay/web', [
        'as'   => 'wxpay_web_cbk_when_user_bevip',
        'uses' => 'User\Hcmvip@bevipWebCallbackWhenWxpay',
    ]);
});


=================share.php==========================
// 标准路由
$app->group([
    'prefix' => 'shareshop/{id}',	// 前缀
    'namespace' => 'User',			// 命名空间
    'middleware' => [				// 中间件
       'jwt_auth',
       'migrate_user_filter',
    ],
], function () use ($app) {
	//GET一般用于获取/查询资源信息，而POST一般用于更新资源信息。
	//1.根据HTTP规范，GET用于信息获取，而且应该是安全的和幂等的。
	//2.根据HTTP规范，POST表示可能修改变服务器上的资源的请求
    $app->post('buyShareShop', 'ShareShop@buyShareShop');
    $app->get('checkIsShareShop', 'ShareShop@checkIsShareShop');
    $app->get('getShareShop', 'ShareShopCtl@getShareShop');
    $app->get('queryShareShop', 'ShareShopCtl@getShareShopIncomeByTime');
});

//例如:localhost/shareshop/77/queryShareShop?ss_id=1&date=2017/8&FromWhere=waimai



=================member.php==========================
//For App
$app->group([
    'prefix' => 'api',
    'middleware' => [
        'simple_jwt_auth'		// 兼容JHWM的JWT
    ],
], function($app) {
    // checkUserIsOpenCloudCollection
    $app->get('cloud/collect/{mobile}','User\CloudCollectionController@checkUserIsOpenCloudCollection');
    //Check API
    $app->post('application/cloud','User\CloudCollectionController@applicationUserCloudCollection');
    //Get Bill
    $app->post('cloud/stream','User\CloudCollectionController@getCollectionStream');
    //APP Pay By Aipay
    $app->post('cloud/alipay','User\CloudCollectionInitiateTransfer@sendMoneyInitiateTransfer');
});

//支付宝App回调
$app->post('mobile/teminal/alipay','User\CloudCollectionInitiateTransfer@mobileMoneyInitiateTransferCallback');
//网页回调
$app->post('wap/teminal/alipay','User\CloudCollectionInitiateTransfer@wapMoneyInitiateTransferCallback');



=================web.php==========================
//商家入驻申请
$app->post('/proxy/application','RegisterController@enter');
//代理登录
$app->group(['prefix'=>'uc'],function() use ($app) {
    //登陆
    $app->post('loginservice', 'AuthController@proxyLogin');
    //验证码登陆
    $app->post('codeservice', 'AuthController@codeLogin');
    //代理忘记密码
    $app->post('revise','AuthController@proxyRevisePassword');
});
//发送验证码
$app->post('/proxy/sms','ToolsController@sendMessage');