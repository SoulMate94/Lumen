<?php

// Tools, static methods only

namespace App\Traits;

class Tool
{
    // Generate inner system trade number
    // 生成内部系统交易号
    // $mid: member id
    // $mtype: 01 => user
    // $domain: 00 => master
    public static function tradeNo(
        $mid = 0,
        $mtype = '01',
        $domain = '00'
    ): string
    {
    	// str_pad--使用另一个字符串填充字符串为指定长度
    	// mb_substr--获取部分字符串
    	// microtime--返回当前 Unix 时间戳和微秒数
        $domain  = str_pad(($domain%42), 2, '0', STR_PAD_LEFT);
        $mid     = str_pad(($mid%1024), 4, '0', STR_PAD_LEFT);
        $mtype   = in_array($mtype, ['01', '02', '03']) ? $mtype : '00';
        $postfix = mb_substr(microtime(), 2, 6);

        return date('YmdHis').$domain.$mtype.$mid.mt_rand(1000, 9999).$postfix;
    }

    // file_exists--检查文件或目录是否存在

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
            $path = resource_path().'/sys_msg/'.$lang;
            if (file_exists($path)) {
                $fsi = new \FilesystemIterator($path);
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
            ? '服务繁忙，请稍后再试'
            : 'Service is busy or temporarily unavailable.'
        );
    }
}
