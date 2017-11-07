<?php

==================app\Models\ToolsModel.php====================================

<?php

namespace App\Models;

use Sysfony\Conponent\HttpKernel\Exception\NotFoundHttpException;
use Jpush\Client;

class ToolsModel
{
    // 地球半径, 评价半径为6371KM
    CONST EARTH_RADIUS = 6371;

    private $timeout = 15;
    private $connect_timeout = 15;
    private $useragent = 'KT-API Client V1.0';

    public function http($url, $params=array(), $method='POST')
    {
        if (! function_exists('curl_init()')) {
            throw new NotFoundHttpException("请安装curl扩展");
        }

        $http = curl_init();
        /* Curl settings */
        curl_setopt($http, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($http, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($http, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($http, CURLOPT_ENCODING, "");
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($http, CURLOPT_HEADER, FALSE);

        $params = http_build_query($params);

        switch ($method) {
            case 'POST':
                curl_setopt($http, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    curl_setopt($http, CURLOPT_POSTFIELDS, $params);
                }
                break;
            case 'PUT' :
                curl_setopt($http, CURLOPT_PUT, true);
                if (!empty($params)) {
                    $url = strpos('?',$url)===false ? "{$url}?{$params}" : "{$url}&{$params}";
                }
                break;
            case 'DELETE':
                curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params)) {
                    $url = strpos('?',$url)===false ? "{$url}?{$params}" : "{$url}&{$params}";
                }
                break;
            case 'GET':
                curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'GET');
                if (!empty($params)) {
                    $url = strpos('?',$url)===false ? "{$url}?{$params}" : "{$url}&{$params}";
                }
        }

        $headers[] = 'API-ClientIP: '.$_SERVER['REMOTE_ADDR'];

        curl_setopt($http, CURLOPT_URL, $url );
        curl_setopt($http, CURLOPT_HTTPHEADER, $headers );
        curl_setopt($http, CURLINFO_HEADER_OUT, TRUE );
        $res = curl_exec($http);

        // 检查是否有错误发送
        if (!curl_errno($http)) {
            $info = curl_getinfo($http);
        }

        curl_close($http);
        return $res;
    }

    /**
     * @desc 计算某个经纬度的周围某段距离的正方形的四个点
     *@param  lng float 经度
     *@param  lat float 纬度
     *@param  distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
     *@return array 正方形的四个点的经纬度坐标
     */
    public function returnSquarePoint($lng,$lat,$distance = 10)
    {
        $dlng =  2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance / self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);
        return [
            'left-top' => ['lat'=> $lat + $dlat,'lng'=> $lng - $dlng],
            'right-top' => ['lat' => $lat + $dlat, 'lng' => $lng + $dlng],
            'left-bottom' => ['lat' => $lat - $dlat, 'lng' => $lng - $dlng],
            'right-bottom' => ['lat' => $lat - $dlat, 'lng' => $lng + $dlng]
        ];
    }

    //计算经纬度距离
    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin( sqrt ( pow (sin($a / 2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2),2) ) ) * 6378.137 * 1000;
        $s = round($s,2);

        return ($s < 1000) ? ( round($s, 2) . 'm') : round( intval($s / 1000).'.'.( $s % 1000), 2).'km';
    }
}