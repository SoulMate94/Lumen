<?php

======================ModelFactory===============================
$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
    ];
});




======================Migrations=================================
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;



public function up()
{
    Schema::create('jh_agent', function (Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->increments('agent_id')->comment('代理商ID');
        $table->bigIncrements('id')->comment('会员提现记录表主键');
        $table->smallIncrements('us_id');
        $table->bigInteger('ref_id')->unsigned()->comment('绑定关系属性关联的主体ID');
        $table->mediumIncrements('id')->comment('绑定关系属性主键');
        $table->tinyInteger('coord_convert')->default(0)->comment('商家坐标是否已经转换过');
        $table->smallInteger('bind_id')->unsigned()->comment('关联的绑定关系主键 => bindings.id');
        $table->unsignedSmallInteger('us_id')->comment('上传场景ID => upload_scenario.us_id');
        $table->string('name', 255)->default('')->comment('代理商名称');
        $table->string('title', 64)->unique()->comment('提现账户显示标题');
        $table->char('mobile', 16)->default('')->comment('手机号')->unique();
        $table->char('tel', 32)->default('')->comment('公司电话')->unique();
        $table->string('pswd', 255)->default('')->comment('密码');
        $table->dateTime('reg_at')->default(date('Y-m-d H:i:s'))->comment('注册时间');
        $table->tinyInteger('status_id')->default('-1')->comment('状态ID');
        $table->mediumInteger('city_id')->default('-1')->comment('城市ID');
        $table->text('area')->comment('代理商在某个城市／区／县得到的划分区域坐标点集合');
        $table->text('notes')->comment('商家的入驻时填写的备注信息');
    });

    DB::statement('ALTER TABLE `jh_agent` comment "代理商主表"');
}


    public function down()
    {
        Schema::dropIfExists('jh_agent');

        Schema::table('jh_shop', function (Blueprint $table) {
            $table->dropColumn('coord_convert');
        });
    }


=======================Seeds=====================================