<?php

Coord--坐标
Convert--转换
Traits--特性

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CoordConvert extends Command
{
    use \App\Traits\CURL;	// 引入CURL

    protected $name = 'patch:coord-convert';
    protected $description = 'Switch all the baidu map coords to amap.';	//将百度坐标转换成高德坐标

    public function fire()
    {
        set_time_limit(0);	// 设置脚本最大执行时间 如果设置为0（零），没有时间方面的限制

        do {
            $coords = \DB::select('
                SELECT `shop_id`, CONCAT(`lng`,",",`lat`) AS `coords`
                FROM `jh_shop`
                WHERE `coord_convert` = 0
                AND `lng` <> ""
                AND `lat` <> ""
                LIMIT 32
            ');

            if (!$coords) {
                exit('No convert jobs found in records.'.PHP_EOL);
                //换行符 PHP_EOL;
				//windows平台相当于    echo "\r\n";
				//unix\linux平台相当于    echo "\n";
				//mac平台相当于    echo "\r";
            }

            $this->patch($coords);
        } while ($coords);
    }

    public function patch($coords)
    {
    	// array_column-- 返回数组中指定的一列
    	// map 方法遍历集合并将每一个值传入给定的回调。该回调可以任意修改项目并返回，从而形成新的被修改过项目的集合：
        $coordStr = implode('|', array_column(collect($coords)
        ->map(function ($x) {
            return (array) $x;
        })
        ->toArray(), 'coords'));

        $api = 'http://restapi.amap.com/'
        .'v3/assistant/coordinate/convert?key='.env('AMAP_KEY')
        .'&locations='.$coordStr
        .'&coordsys=baidu'
        .'&output=json';

        $apiRes = $this->requestJsonApi($api, 'GET');

        if ((0 !== $apiRes['err']) ||
            (
                isset($apiRes['res']['status']) &&
                $apiRes['res']['status']=='0'
            ) || (
                !isset($apiRes['res']['locations']) ||
                !$apiRes['res']['locations']
            )
        ) {
            exit(
                'A call to Amap was failed, please try again later.'
                .PHP_EOL
                .'('
                .$apiRes['res']['info']
                .')'
            );
        }

        $locations = explode(';', $apiRes['res']['locations']);

        if (!$locations || (count($coords) != count($locations))) {
            exit('Exception occurred: counts unmatched');
        }

        foreach ($coords as $key => &$coord) {
            $_coord  = explode(',', $locations[$key]);
            $updated = \DB::table('jh_shop')
            ->where('shop_id', $coord->shop_id)
            ->update([
              'lng' => $_coord[0],
              'lat' => $_coord[1],
              'coord_convert' => 1,
            ]);

            //usleep — 以指定的微秒数延迟执行
            usleep(500000);

            echo 'Coords of shop #',
            $coord->shop_id,
            ' converted ',
            ($updated ? 'successfully' : 'failed'),
            PHP_EOL;
        }

        echo 'Done!', PHP_EOL;
    }
}
