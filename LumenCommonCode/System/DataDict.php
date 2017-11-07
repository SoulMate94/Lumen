<?php

数据库#

============================Routes=============================================
$app->group([
    'prefix'     => 'sys',
    'namespace'  => 'Admin',
], function () use ($app) {
    $app->group([
        'middleware' => [
            'admin_auth',
        ],
    ], function () use ($app) {
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



===================app\Http\Controllers\Admin\DataDict.php=====================
<?php

namespace App\Http\Controllers\Admin;

class DataDict
{
    private $currentDB = null;

    public function __construct()
    {
        $database   =   config('database');
        $default    =   $database['default'];
        $conns      =   $database['connections'];
        $this->currentDB = $conns[$default]['database'];
    }

    public function index()
    {
        $tables = \DB::select('
            SELECT
                `t`.`TABLE_NAME` AS tbName,
                `t`.`TABLE_TYPE` AS tbType,
                `t`.`ENGINE` AS tbEngine,
                `t`.`ROW_FORMAT` AS tbRowFormat,
                `t`.`AUTO_INCREMENT` AS tbAutoIncre,
                `t`.`CREATE_TIME` AS tbCreateAt,
                `t`.`UPDATE_TIME` AS tbUpdateAt,
                `t`.`TABLE_COLLATION` AS tbCollation,
                `t`.`TABLE_COMMENT` AS tbComment
            FROM `information_schema`.`tables` `t`
            WHERE `t`.`TABLE_SCHEMA` = "'.$this->currentDB.'"
        ');

        $tbName = array_unique(array_column($tables, 'tbName'));

        return view('admin.dd', compact('tbNames', 'tables'));
    }

    public function getFields()
    {
        $fieldsRaw = \DB::select('
            SELECT
                `c`.`TABLE_NAME` AS tbName,
                `c`.`COLUMN_NAME` AS fdName,
                `c`.`COLUMN_DEFAULT` AS fdDefault,
                `c`.`IS_NULLABLE` AS fdNullable,
                `c`.`COLUMN_TYPE` AS fdType,
                `c`.`CHARACTER_SET_NAME` AS fdCharset,
                `c`.`COLLATION_NAME` AS fdCollation,
                `c`.`PRIVILEGES` AS fdPriv,
                `c`.`COLUMN_KEY` AS fdKey,
                `c`.`COLUMN_COMMENT` AS fdComment
            FROM`information_schema`.`columns` `c`
            WHERE `c`.`TABLE_SCHEMA` = "'.$this->currentDB.'"
        ');

        $fields = [];
        foreach ($fieldsRaw as $field) {
            $fields[$field->tbName][] = [
                'fdName'      => $field->fdName,
                'fdType'      => $field->fdType,
                'fdComment'   => $field->fdComment,
                'fdKey'       => $field->fdKey,
                'fdCharset'   => $field->fdCharset,
                'fdCollation' => $field->fdCollation,
                'fdNullable'  => $field->fdNullable,
                'fdDefault'   => $field->fdDefault,
                'fdPriv'      => $field->fdPriv,
            ];
        }

        return response()->json($fields, 200);
    }
}


