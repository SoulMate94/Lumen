<?php

Support--支持
ServiceProvider--服务提供者

=====================AppServiceProvider=============================
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 注册任何应用程序服务。
     *
     * @return void
     */
    public function register()
    {
        //
    }
}


=====================AuthServiceProvider=============================
Facades--门面
Gate--门
Authenticated--认证
Instance--实例
Obtain--获得
Via--通过

namespace App\Providers;

use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
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

    /**
     * Boot the authentication services for the application.
     * 启动认证事务的应用程序。
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('api_token')) {
                return User::where('api_token', $request->input('api_token'))->first();
            }
        });
    }
}


=====================CommandServiceProvider=============================
Singleton--单例

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CommandService extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('command.patch.coord-convert', function () {
            return new \App\Console\Commands\CoordConvert;
        });

        $this->commands(
            'command.patch.coord-convert'	// 点--数组
        );
    }
}


=====================EventServiceProvider=============================
namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     * 该应用程序的事件监听器映射。
     *
     * @var array
     */
    protected $listen = [
        'App\Events\SomeEvent' => [
            'App\Listeners\EventListener',
        ],
    ];
}
