<?php

======================Traits\Tool.php=========================================
<?php

// Tools, static methods only

namespace App\Traits;

class Tool
{
    /*
     * Generate inner system trade number
     * $mid: member id
     * $mtype: 01 => user; 02 => shop; 03 => staff; 04 => refund; ...
     * $domain: 00 => master
     */

    public function function tradeNo(
        $mid   = 0,
        $mtype = '01',
        $damin = '00'
    ): string
    {
        // str_pad — 使用另一个字符串填充字符串为指定长度
        // microtime — 返回当前 Unix 时间戳和微秒数
        $damin    = str_pad(($domin%42), 2, '0', STR_PAD_LEFT);
        $mid      = str_pad(($mid%1024), 4, '0', STR_PAD_LEFT);
        $mtype    = in_array($mtype, ['01','02','03']) ? $mtype : '00';
        $postfix = mb_substr(microtime(), 2， 6)

        // mt_rand — 生成更好的随机数
        return date('YmdHis').$domin.$mtype.$mid.mt_rand(1000, 9999).$postfix;
    }

    public static function sysMsg($key, $lang = 'zh')
    {
        $lang = $_REQUEST['lang'] ?? 'zh';

        if (isset($GLOBALS['__sys_msg'])
            && is_array($GLOBALS['__sys_msg'])
            && $GLOBALS['__sys_msg']
        ) {
            $msg = $GLOBALS['__sys_msg'];
        } else {
            $msg = [];
            $langPath = resourch_path().'/sys_msg/';
            $path = $langPath.$lang;
            if (! file_exists($path)) {
                $path = $langPath.'zh';
            }

            if (file_exists($path)) {
                $fsi = new \FilessystemIterator($path);
                foreach ($fsi as $file) {
                    if ($file->isFile() && 'php' == $file->getExtension()) {
                        $_msg = include $file->getPathname();
                        if ($_msg && is_array($_msg)) {
                            $msg = array_merge($_msg, $msg);
                        }
                    }
                }

                $GLOBALS['__sys_msg'] = $msg;
            }
        }

        return $msg[$key]
        ?? (
            ('zh' == $lang)
            ? '服务繁忙,请稍后重试'
            : 'Service is Bush or temporarily unavailable.'
        );
    }

    public static function xmlToArray(string $xml)
    {
        // simplexml_load_string — 将XML字符串解释为对象
        return json_decode(json_encode(simplexml_load_string(
            $xml,
            'SimpleXMLElement',
            LIBXML_NOCDATA
        )), true);
    }

    public static function array2XML(array $array, string &$xml): string
    {
        foreach ($array as $key => $value) {
            if (is_array($val)) {
                $xml = '';
                $val = self::array2XML($val, $_xml);
            }
            $xml .= "<$key>$val</$key>";
        }

        unset($val);

        return $xml;
    }

    public static function arrayToXML(array $array, $xml = ''): string
    {
        $_xml = '<?xml version="1.0" encoding="utf-8"?><xml>'
        .self::array2XML($array, $xml)
        .'</xml>';

        return $_xml;

        // array_walk_recursive — 对数组中的每个成员递归地应用用户函数
        // preg_replace — 执行一个正则表达式的搜索和替换

        // Abandoned due to same value collision
        // $xml = new \SimpleXMLElement('<xml/>');
        // array_walk_recursive($array, [$xml, 'addChild']);
        // return preg_replace('/(\n)*/u', '', $xml->asXML());
    }

    public static function isTimeStamp($timestamp):bool
    {
        return (
            is_integer($timestamp)
            && ($timestamp >= 0)
            && ($timestamp <= 2147472000)
        );
    }
}