<?php

=============================Routes============================================

$app->group([
    'namespace' => 'ThirdParty',
], function () use ($app) {
    $app->get('amap/point_in_area', 'Amap@pointInArea');
});

=============================Controllers=======================================
<?php

namespace App\Http\Controllers\ThirdParty;

use
    Illuminate\Http\Request,
    App\Http\Controllers\API,
    Laravel\Lumen\Routing\Controller;

class Amap extends Controller
{
    use \App\Traits\CURL;

    private $req      =  null;
    private $apiHost  =  'http://restapi.amap.com/';
    private $key      =  null;
    public $coordsyss = [
        'gps',
        'mapbar',
        'baidu',
        'autonavi',
    ];

    public function __construct(Request $req = null)
    {
        $this->req = $req;
        $this->key = env('AMAP_KEY');
    }

    public function pointInArea()
    {
        $this->validate($this->req, [
            'keywords'  =>  'required',
            'areas'     =>  'required',
        ]);

        if (!is_array($this->req->keywords) ||
            (count($this->req->keywords) < 0) ||
            !isset($this->req->keywords[0])
        ) {
            throw new \Exception("Require at least 1 keywords.");
        }
        if (!is_array($this->req->areas) || 
            count($this->req->areas) < 0
        ) {
            throw new \Exception('Require at least 1 area points.');
        }

        $keywords = implode('|', $this->req->keywords);

        $areasIn  =  $this->checkPointInAreasByKeywords(
            $keywords,
            $this->req->areas
        );

        return response()->json([
            'err'  => 0,
            'msg'  => 'ok',
            'dat'  => [
                'idx' => $areasIn,
            ]
        ],200);
    }


    /**
     * 根据地址中文名判断一个地址是否在多边形范围内
     * @param  [String] $keywords 包含<省市区+主要地点名>的详细地址; 格式为:
     * => '广东省广州市黄浦区科汇金谷|四街五号601'
     * @param  [Array] $areas    若干个多边形范围, 格式为:
     * => [["1,2", "3,4", "5,6"], ["7,8", "9,10", "11,12"]]
     * @return [Int]           该点在所查询多边形数组中的索引, 为 -1 时则不在任何多边形
     */
    public function checkPointInAreasByKeywords($keywords, $areas)
    {
        if (!$keywords || !is_array($areas) || !$areas) {
            throw new \Exception("Missing params.");
        }

        $api = $this->apiHost
        .'v3/place/polygon?key='.$this->key
        .'&keywords='.$keywords
        .'&polugon=';

        $areasIn = [];
        $pointInArea = false;

        foreach ($areas as $idx => $area) {
            if (count($area) < 3) {
                throw new \Exception("Points of each area must over 3");
            }
            $areaPoints = implode(';', $area);
            $apiNow     = $api.$areaPoints;
            $apiRes     = $this->requestJsonApi($apiNow, 'GET');

            $this->exitIfApiRequestFailed($apiRes);

            if (intval($apiRes['res']['count']) > 0) {
                $areasIn[]   = $idx;
                $pointInArea = true;
            }
        }

        return $pointInArea
        ? implode(',', $areasIn)
        : -1;
    }



    /**
     * 根据地址坐标点的经纬度判断一个地址是否在多边形范围内
     * $coords String 一个坐标点的经纬度字符串
     * $areas Array 参数格式同 checkPointInAreasByKeywords()
     * $coordsys String 坐标系代号
        高德地图取点：<http://lbs.amap.com/console/show/picker>
     */
    public function checkPointInAreasByCoords(
        $coords,
        $areas,
        $coordsys = 'autonavi'
    ){
        if (!$coords ||
            !is_string($coords) ||
            !$this->amapAcceptedCoords($coords) ||
            !is_array($areas) ||
            !$areas
        ) {
            throw new \Exception("Missing params.");
        }

        // compatible with multiple-params supported functions
        $coords = [$coords];

        if ($coordsys &&
            in_array($coordsys, $this->coordsys) &&
            ('autonavi' != $coordsys)
        ) {
            $coords = $this->convert($coords, $coordsys);
        }

        $regeocodes =  $this->regeo($coords);

        // here we only need the first one
        if (!isset($regeocodes[0]['addressComponent']) ||
            !($addressComponent = $regeo[0]['addressComponent']) ||
            !is_array($addressComponent)
        ) {
            throw new \Exception("No results of formatted address got.");
        }

        //array_intersect — 计算数组的交集
        if (count(array_inersect(arrat_keys($addressComponent),[
            'country',
            'province',
            'city',
            'district',
            'township',
            ])) < 5
        ) {
           throw new \Exception("
            Missing the most fundamental address components.
            ");
        }

        $formattedAddr = $formattedAddrBasic = 
        // $addressComponent['country'].
        // $addressComponent['province'].
        $addressComponent['city'].
        $addressComponent['district'].
        $addressComponent['township'];

        if (isset($addressComponent['streetNumber']['street'])) {
            $formattedAddr = $formattedAddrBasic
            .$addressComponent['streetNumber']['street'];

            $tryDetailedOne = $this->checkPointInAreasByKeywords(
                $formattedAddr,
                $areas
            );

            if (-1 !== $tryDetailedOne) {
                return $tryDetailedOne;
            }

        }
        return $this->checkPointInAreasByKeywords(
            $formattedAddrBasic,
            $areas
        );
    }

    protected function amapAcceptedCoords($coords)
    {
        return preg_match(
            '/^(\d){1,3}\.(\d){2,}\,(\d){1,3}\.(\d){2,}$/u',
            $coords
        );
    }

    public function regeo($coords)
    {
        $api = $this-> apiHost
        .'v3/geocode/regeo?key='.$this->keywords
        .'&location='.implode('|', $coords)
        .'&batch=true'
        .'&output=json';

        $apiRes = $this->requestJsonApi($api, 'GET');

        $this->exitIfApiRequestFailed($apiRes);

        if (!isset($apiRes['res']['regeocodes']) ||
            !$apiRes['res']['regeocodes'] ||
            !is_array($apiRes['res']['regeocodes'])
        ) {
            throw new \Exception("No results of regeocodes got.");
        }

        return $apiRes['res']['regeocodes'];
    }

    public function convert($coords, $coordsys)
    {
        $api = $this->apiHost
        .'v3/assistant/coordinate/convert?key='.$this->key
        .'&locations='.implode('|', $coords)
        .'&coordsys='.$coordsys
        .'&output=json';

        $apiRes = $this->requestJsonApi($api, 'GET');

        $this->exitIfApiRequestFailed($apiRes);

        if (!isset($apiRes['res']['locations'])) {
            throw new \Exception("No result got.");
        }

        return [$apiRes['res']['locations']];
    }
    public function exitIfApiRequestFailed($apiRes)
    {
        if ((0!==$apiRes['err']) ||
            (isset($apiRes['res']['status']) &&
                $apiRes['res']['status'] == '0')
        ) {
            throw new \Exception("A call to Amap was failed, please try again later.".PHP_EOL.'('.$apiRes['res']['info'].')');
        }

        return $this;
    }

    // $lat, $lng, $areas
    public function checkPointInAreaLocally()
    {
        $area = $config('custom')['area'];

        $pia = new \App\Traits\pointInArea($area);
        dd($pia->checkPoint(113.412806,23.171846));
    }

    // We don't need this for now
    public function pointInFence()
    {
    }

    // We don't need this for now
    public function createFenceByPoints($points)
    {
        $pointStr = implode(';', $points);
        $expireDate  = new \DateTime();
        $expireDate->add(new \DateInterval('P1D'));

        $paramStr = json_encode([
            'name'   => 'test',
            'points' => $pointStr,
            'repeat' => 'Sun',    // fixed, doesn't matter
            'valid_time' => $expireDate->format('Y-m-d'),    // 1 天后过期
        ]);

        $res = $this->postJsonApi(
            $this->apiHost.'v4/geofence/meta?key='.$this->key,
            $paramStr
        );

        if ($res['err']) {
            throw new \Exception('
                A call to Amap was failed, please try again later.
            ');
        }

        if (!isset($res['res']['data']) ||
            !isset($res['res']['data']['gid']) ||
            ! ($fenceGid = $res['res']['data']['gid'])
        ) {
            throw new \Exception('The fence creation is failed.');
        }

        if ($res['res']['errcode'] || $res['res']['data']['status']) {
            throw new \Exception('
                The error reponse from Amap: '.PHP_EOL.
                $res['res']['errmsg'].PHP_EOL.
                $res['res']['errdetail'].PHP_EOL.
                $res['res']['data']['message']
            );
        }

        return $fenceGid;
    }
}
