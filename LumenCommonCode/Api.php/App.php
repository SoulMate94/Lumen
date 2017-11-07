<?php

// Autoload
require_once __DIR__.'/../vendor/autoload.php';


$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);


// Create The Application
$app->configure('database');
$app->configure('custom');


$app->withFacades();
$app->withEloquent();


// Register Container Bindings
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);


// Register Middleware
// 创建全局的中间件
$app->middleware([
   'cors' => App\Http\Middleware\CORS::class,
]);

$app->routeMiddleware([
    'jwt_auth'   => App\Http\Middleware\Auth\JWT::class,
    'admin_auth' => App\Http\Middleware\Auth\Admin::class,
    'qiniu_auth' => App\Http\Middleware\Auth\Qiniu::class,
    'ak_sk_auth' => App\Http\Middleware\Auth\AKSK::class,
    'simple_jwt_auth' => App\Http\Middleware\Auth\JWTSimple::class,
    'migrate_user_filter' => App\Http\Middleware\Filter\MigrateUser::class,
    'jwt'        => App\Http\Middleware\CheckUserJwt::class,
]);


// Register Service Providers
// 需要什么服务就注册什么服务
$app->register(App\Providers\CommandService::class);
$app->register(Latrell\Alipay\AlipayServiceProvider::class);

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);

//excel
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
//redis
$app->register(Illuminate\Redis\RedisServiceProvider::class);


// Load The Application Routes
// 新增路由需要注册
$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require_once __DIR__.'/../routes/web.php';
    require_once __DIR__.'/../routes/api.php';
    require_once __DIR__.'/../routes/api.migrate.php';
    require_once __DIR__.'/../routes/staff.php';
    require_once __DIR__.'/../routes/member.php';
    require_once __DIR__.'/../routes/share.php';
});

return $app;