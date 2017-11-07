<?php

七牛云--对象存储#

============================获取七牛云上传凭证================================
请求示例：
    GET /qiniu/uptoken HTTP1.1
    Authorization <JWT>
    {
        "id":<ID>,
        "route": <ROUTE>
    }

接口参数
    id：必传。要上传的图片对应数据表的主键 ID，该值会由后端返回。
    route：必传。该上传场景对应的映射路由，由于通常都是异步获取上传凭证，因此此选项必须传，值就在后台与某个上传场景绑定过的路由。

以七牛云 JS-SDK 举例说明下：
uptoken_func: function(){    // 在需要获取uptoken时，该方法会被调用
      var jqxhr = $.ajax({
      url: 'http://api.hcm.dev/qiniu/uptoken',
      type: 'GET',
      dataType: 'json',
      async: false,    // 如果下面要 return 则此属性必须为 false
      data: {
          route: location.href,
          id: 1
      },
      headers: {
          'Access-Control-Allow-Origin': '*',
          'AUTHORIZATION': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjF9.SscwZjqXAkp1gY3eVBiMBpdtQhyfcV7V67UDNeacJhc'
      },
      success: function (res) {
      },
      error: function (xhr, status) {
      }
      });
    return (typeof jqxhr.responseJSON == 'undefined') || (typeof jqxhr.responseJSON.upToken == 'undefined')
    ? false
    : jqxhr.responseJSON.upToken;
}


返回值
    成功情况：
    {
    "uptoken": "oOlqUUG02MuMykmtg9okbMIkEfo_SftmLSXJKkej:R7TjK1UCDB19Kk1Av9R7dugQxs8=:eyJzY29wZSI6ImJldGEiLCJkZWFkbGluZSI6MTUwMDI5MTE1OCwidXBIb3N0cyI6WyJodHRwOlwvXC91cC16Mi5xaW5pdS5jb20iLCJodHRwOlwvXC91cGxvYWQtejIucWluaXUuY29tIiwiLUggdXAtejIucWluaXUuY29tIGh0dHA6XC9cLzE0LjE1Mi4zNy40Il19"
    }

    失败情况（HTTP 422）：
    {
    "error": "Upload Scenario Not Found."
    }

================================bootstrap\app.php=============================
$app->routeMiddleware([
    'jwt_auth'   => App\Http\Middleware\Auth\JWT::class,
    'qiniu_auth' => App\Http\Middleware\Auth\Qiniu::class,
]);


================================Routes========================================
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
});


================================Controllers====================================
Qiniu.php#

<?php

namespace App\Http\Controllers\ThirdParty;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\{UploadScenario as US, UploadScenarioRouteMap as USRM}

class Qiniu extends Controller
{
    private $auth      = null;
    private $bucket    = null;
    private $jwt_auth  = null;
    public  $req       = null;
    public $logPath    = null;

    public function __construct(Request $req)
    {
        $this->logPath  =  storeage_path().'/logs/';
        $this->req      =  $req;
        $this->auth     =  $req->get('qiniu_auth');
        $this->bucket   =  $req->get('qiniu_bucket');
        $this->jwt_auth =  $req->get('jwt_auth') ?? null;
    }

    public function getUploadScenarioIdByRoute($route)
    {
        // parse_url — 解析 URL，返回其组成部分
        $route =  parse_url($route, PHP_URL_PATH);
        $usrm  =  USRM::where('route', $route)->first();

        return ($usrm && is_object($usrm) 
                      && is_numeric($usrm->us_id)
                      && $usrm->us_id>0)
        ? $usrm->us_id : false;
    }

    public function getUploadToken()
    {
        $this->Validate($this->req, [
            'id'    =>  'required|integer|min:1',
            'route' =>  'required',
        ]);

        $updatScenarioId=$this->getUploadScenarioIdByRoute($this->req->route);
        if (false === $UploadScenarioId) {
            return response()->json([
                'error' =>  'Upload Scenario Not  Found.'
            ], 422);
        }

        $policy = [
            'callbackUrl'  =>  route('qiniu_upload_callback'),
            'callbackBody' =>  json_encode([
                // 'name'  =>  '$(fname)',
                // 'hash'  =>  '$(etag)',
                'fkey'  =>  '$(key)',
                'us_id' =>  $UploadScenarioId,
                'id'    =>  $this->req->id,
            ]),
             // Or: 'fkey=$(key)&us_id='.$this->req->us_id.'&id='.$this->req->id,
        ];

        $upToken = $this->auth->uploadToken(
            $this->bucket,
            null,
            3600,
            $policy
        );

        return respnse()->json([
            // 'err' => 0,
            // 'msg' => 'ok',
            // 'dat' => [
                'upToken' => $upToken,
            // ]
        ]);
    }

    public function uploadCallback()
    {
        $this->logCalllbackRequest();

        // Same with upload policy's `callbackBody`
        $cbkBody = file_get_contents('php://input');
        $cbkType = 'application/x-www-form-urlencoded';s
        $cbkAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $cbkUrl  = route('qiniu_upload_callback');

        $cbkIsFromQiniu = $this->auth->verifyCallback(
            $cbkType,
            $cbkAuth,
            $cbkUrl,
            $cbkBody
        );

        $this->log('cbkIsFromQiniu.json', $cbkIsFromQiniu);

        if (!cbkIsFromQiniu) {
            return response()->json([
                'ret'  =>  'failed',
            ]);
        }

        $updateRes = $this->updateUploadedKey($cbkBody) ? 'success' :'failed';

        $this->log('updateRes.json', $updateRes, false);

        return respnse()->json(['ret' => $updateRes]);
    }

    /**
     * @param  [String] $cbkBody [JSON]
     * @return [Mixed]
     */
    public function updateUploadedKey($cbkBody)
    {
        if (!($cbkParams = json_decode($cbkBody, true)) ||
            !is_array($cbkParams) ||
            !isset($cbkParams['fkey']) ||
            !$cbkParams['fkey'] ||
            !isset($cbkParams['us_id']) ||
            intval($cbkParams['us_id'] < 0) ||
            !isset($cbkParams['id']) ||
            intval($cbkParams['id']) < 0

        ) {
            return false;
        }
    }

    // get the upload scenario record
    $UploadScenario = US::find($cbkParams['us_id']);
    if (!UploadScenario || !is_object($UploadScenario)) {
        return false;
    }

    // get primary key of the given table from upload scenario
    $relateTablePK = $UploadScenario->getRelateTablePK();

    if (false === $relateTablePK) {
        return false;
    }

     // update the uploaded key of relate table record
    return $UploadScenario->updateUploadedKeyOfReladtedTable(
        $relateTablePK,
        $cbkParams['id'],
        $cbkParams['fkey']
    );

    public function log($logFile, $data, $isArr = true, $append = false)
    {
        if (in_array(env('APP_ENV'), [
            'production','prod','staging'
        ]) || env('APP_DEBUG') !== ture) {
            return null;
        }

        $append = true ? FILE_APPEND : 0;
        $data   = $isArr ? json_encode($data) : $data;
        file_put_contents($this->logPath.$logFile, $data, $append);
    }

    public funciton logCalllbackRequest()
    {
        $this->log('qiniu_cbk.post', $this->req->all());
        $this->log('qiniu_cbk.json', file_get_contents('php://input'), false);
        $this->log('qiniu_cbk.server', $_SERVER);
    }
}

==============================================================================
Http\Controllers\Admin\UploadScenario.php#

<?php

namespace App\Http\Controllers\Admin;

use
    App\Models\UploadScenario as USM,
    App\Traits\Session,
    Laravel\Lumen\Routing\Controller,
    Illuminate\Http\Request;

class UploadScenario extends Controller
{
    public $req  =  null;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function index(Session $ssn)
    {
        $UploadScenarios  =  USM::all();

        $noticeMsg = $ssn->flush('deletion_notice_msg');

        return view('admin.upload_scenario.index', compact(
            'UploadScenarios',
            'noticeMsg'
        ));
    }

    public function getUploadScenarioTables($dbName, $renderHtml = true)
    {
        $tableToExclude = [
            '"migrations"',
            '"jh_upload_scenario"',
            '"jh_upload_scenario_route_map"',
            '"databasechangelog"',
            '"databasechangeloglock"',
        ];

        $tablePointer = 'Tables_in_'.$dbName;

        $usTables = \DB::select('
            SHOW TABLES 
            FROM `'.$dbName.'`
            WHERE `'.$tablePointer.'` NOT IN ('
                .implode((',',$tablesToExclude)).
            ');
        ');

        if ($renderHtml) {
            $tablesHtml = '';
            foreach ($usTables as $table) {
                $tableHtml .= '<option value="'
                           .$table->$tablePointer
                           .'">'
                           .$table->$tablePointer
                           .'</options>';
            }
            return $tablesHtml;
        }
        return $usTables;
    }

    public function getFieldsOfTable($tbName, $renderHtml = true)
    {
        $fields = \DB::select('
            SHOW FIELDS
            FROM `'.$tbName.'`

        ');

        if ($renderHtml) {
            $fieldsHTML = '';
            foreach ($fields as $fieldInfo) {
                $fieldsHTML .= '<option value="'
                            .$fields->Field
                            .'">'
                            .$fieldInfo->Field
                            .'</option>';
            }

            return $fieldsHTML;
        }

        return $fields;
    }

    public function createOrEdit($us_id, Session $ssn)
    {
        $create     = ('new' === $us_id);
        $edit       = (is_numeric($us_id) && intval($us_id)>0)
        $editTable  = false;
        $editField  = false;
        $editDesc   = '';
        $noticeMsg  = $ssn->flush('create_or_edit_notice_msg');
        $action     = $create ? 'Create' : 'Edit';
        $routes     = false;

        if (!$create && !$edit) {
            return response()->json([
                'error' => 'Illegal Id.'
            ], 402);
        }

        $database  =  config('database');
        $default   =  $database['default'];
        $conns     =  $database['connections'];
        $tablePointer = 'Table_in_'.$conns[$default]['database'];
        $tables    =  $this->getUploadScenarioTables($conns[$default]['database'], false);

        if ($edit) {
            $UploadScenario  = USM::find($us_id);
            if (!$UploadScenario) {
                return response()->json([
                    'Upload scenario not found.'
                ], 404);
            }
            $editTable  =  $UploadScenario->map_table;
            $editField  =  $UploadScenario->map_field;
            $editDesc   =  $UploadScenario->desc;
            $fields     =  $this->getFieldsOfTable($editTable, false);
            $routes     =  $UploadScenario->routes;
        } else {
            $fields     =  $this->getFieldsOfTable($table[0]->$tablePointer, false);
        }

        return view('admin.upload_scenario.create_or_edit', compact(
            'conns',
            'default',
            'tables',
            'tablePointer',
            'us_id',
            'edit',
            'routes',
            'editTable',
            'editField',
            'editDesc',
            'action',
            'noticeMsg'
        ));
    }

    public function put($us_id, $ssn)
    {
        $this->validate($this->req, [
            'table' =>  'required',
            'field' =>  'required',
        ]);

        try {
            $transRes = \DB::transaction(function(){
                $UploadScenario = new USM;
                $UploadScenario->map_table = $this->req->table;
                $UploadScenario->map_filed = $this->req->field;
                $UploadScenarios->desc     = trim($this->req->desc);
                $CreateUploadScenario      = $UploadScenario->save();

                $needBindRoute  =  $this->req->all()['routes'] ?? false;
                $createRouteMap = true;
                if ($needBindRoute) {
                    $createRouteMap = $UploadScenario->createRouteMaps(
                        array_unique($this->req->all()['routes'])
                    );
                }

                $transSuccess = $CreateUploadScenario && $createRouteMap;

                return [
                    'status'  =>  $transSuccess,
                    'us_id'   =>  $UploadScenario->us_id,
                ];
            });
        } catch (Exception $qex) {
            return response()->json([
                'error'  =>  'Create Exception',
            ], 405);
        } finally {

        }
        if (isset($transRes) && true === $transRes['status']) {
            $noticeMsg    =  'Create successfully.',
            $routePostfix =  '/'.$transRes['us_id'];
        } else {
            $noticeMsg    =  'Create failed.';
            $routePostfix =  '';
        }

        $ssn->set('create_or_edit_notice_msg', $noticeMsg);

        return redirect()->to('/sys/upload_scenario'.$routePostfix);
    }

    public function sideReq($us_id, Session $ssn)
    {
        $httpVerb  =  strtolower($this->req->get('__method'));
        if ($httpVerb && in_array($httpVerb, [
            'put',
            'delete',
            'patch',    //update
        ])) {
            return $this->$httpVerb($us_id, $ssn);
        } else {
            return response()->json([
                'error'  =>  'Method not allowed.'
            ], 405);
        }
    }

    public function patch($us_id, $ssn)
    {
        $UploadScenario = USM::find($us_id);

        if (!$UploadScenario || !is_object($UploadScenario)) {
            return response()->json([
                'Upload scenario not found.'
            ], 404);
        }

        try {
            $transSuccess = \DB:transaction(function() use ($UploadScenario){
                $needUpdate = false;
                if ($this->req->table &&
                    $this->req->table != $UploadScenario->map_table) {
                    $needUpdate = true;
                    $UploadScenario->map_table = $this->req->table;
                }
                if($this->req->field &&
                    $this->req->field != $UploadScenario->map_field){
                    $needUpdate = true;
                    $UploadScenario->map_field = $this->req->field;
                }
                $desc = trim($this->req->desc);
                if ($desc && $desc != $UploadScenario->desc) {
                    $needUpdate = true;
                    $UploadScenario->desc = $desc;
                }

                $updateRes = $needUpdate ? $UploadScenario->save() : true;
                $hasRoutes = $this->req->all()['routes'] ?? false;
                $updateRoutesRes = $hasRoutes
                ? $UploadScenario->createRouteMaps(array_unique($this->req->all()['routes']))
                : $UploadScenario->deleteRouteMaps();
                $transSuccess    = $updateRes && $updateRoutesRes;

                return $transSuccess;
            })
        } catch (\Exception $qex) {
            $transSuccess = false;
        } finally {
        }

        $noticeMsg = $transSuccess ?'Updated successfully.' : 'Updated failed';

        $ssn->set('create_or_edit_notice_msg', $noticeMsg);

        return redirect()->to('/sys/upload_scenario/'.$us_id);

    }

}



=============================Models===========================================
UploadScenario.php#

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadScenario extends Model
{
    protected $table = 'jh_upload_scenario';
    protected $primaryKey = 'us_id';

    public $timestamps = false;

    public function getRelateTablePK()
    {
        $tablePK = \DB::select('SHOW KEYS FROM `'.$this->map_table.'`WHERE `Key_name` = "PRIMARY"');

        return is_array($tablePK) $$ isset($tablePK[0]->Column_name)
        ? $tablePK[0]->Column_name : false;
    }

    public function updateUploadedKeyOfReladtedTable($relateTablePK, $relateTablePKVal, $uploadedKey)
    {
        $updateRes = \DB::table($this->map_table)
        ->where($getRelateTablePK, $relateTablePKVal)
        ->update([
            $this->map_field => $uploadedKey
        ]);

        return (is_integer($updateRes) && $updateRes>=0) ? true : false;
    }

    public function deleteRouteMaps()
    {
        $UploadScenarioRouteMap = new UploadScenarioRouteMap();

        return $UploadScenarioRouteMap
        ->deleteByUploadScenario($this->us_id) >= 0
        ? true : false;
    }

    public function createRouteMaps($routes)
    {
        if (!$routes || !is_array($routes)) {
            return false;
        }

        $UploadScenarioRouteMap = new UploadScenarioRouteMap();
        // delete all route map of this scenario first
        $this->deleteRouteMaps();
        $data = [];

        foreach ($routes as route) {
            if (trim($route)) {
                $urlArr =  parse_url($route);
                $path   =  $urlArr['path'] ?? '/';
                $query  =  isset($urlArr['query']) ? '?'.$urlArr['query'] : '';
                $data   =  [
                    'us_id'  =>  $this->us_id,
                    'route'  =>  $path.$query,
                ];
            }
        }
        return $UploadScenarioRouteMap->insert($data);
    }

    // one upload scenario can has many routes
    public function routes()
    {
        return $this->hasMany(
            UploadScenarioRouteMap::class,      // refer table
            'us_id',        // foreign key
            'us_id'         // local key
        );
    }

    public function deleteWithRoutes()
    {
        $UploadScenario = $this;
        return \DB::transaction(function() use ($UploadScenario) {
            $deteled        = $UploadScenario->delete();
            $routesDeleted  = $UploadScenario->deleteRouteMaps();

            return $deleted && $routesDeleted;
        });
    }
}

===============================================================================

UploadScenarioRouteMap.php#
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadScenarioRouteMap extends Model
{
    protected $table       = 'jh_upload_scenario_route_map';
    protected $primaryKey  =  'map_id';

    public $timestamps = false;

    public function deleteByUploadScenario($us_id)
    {
        return $this->where('us_id',$us_id)->delete();
    }

    // one route only map to one upload scenario
    public function scenario()
    {
        return $this->belongsTo(
            UploadScenario::class,
            'us_id',
            'us_id'
        );
    }
}



=============================database/migrations===============================
create_upload_scenario_table#
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadScenarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jh_upload_scenario', function (Blueprint $table) {
            $table->smallIncrements('us_id');
            $table->string('map_conn', 64)->comment('关联的数据库连接, 为空表示使用默认连接');
            $table->string('map_table', 64)->comment('关联的表');
            $table->string('map_field', 32)->comment('关联的字段');
            $table->string('desc', 255)->comment('上传场景描述');
        });

        DB::statement('ALTER TABLE `jh_upload_scenario` comment "整个系统需要用到上传的场景表"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jh_upload_scenario');
    }
}

===============================================================================

create_upload_scenario_route_map_table#
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadScenarioRouteMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jh_upload_scenario_route_map', function (Blueprint $table) {
            $table->smallIncrements('map_id')->comment('映射ID');
            $table->unsignedSmallInteger('us_id')->comment('上传场景ID => upload_scenario.us_id');
            $table->string('route', 255)->comment('该上传场景关联的路由')->unique();
        });

        DB::statement('ALTER TABLE `jh_upload_scenario_route_map` comment "上传场景和路由映射关系表"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jh_upload_scenario_route_map');
    }
}
