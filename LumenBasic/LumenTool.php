<?php

// Tools, static methods only

namespace App\Traits;

class Tool
{
    // Generate inner system trade number
    // $mid: member id
    // $mtype: 01 => user
    // $domain: 00 => master
    public static function tradeNo(
        $mid = 0,
        $mtype = '01',
        $domain = '00'
    ): string
    {
        $domain  = str_pad(($domain%42), 2, '0', STR_PAD_LEFT);
        $mid     = str_pad(($mid%1024), 4, '0', STR_PAD_LEFT);
        $mtype   = in_array($mtype, ['01', '02', '03']) ? $mtype : '00';
        $postfix = mb_substr(microtime(), 2, 6);

        return date('YmdHis').$domain.$mtype.$mid.mt_rand(1000, 9999).$postfix;
    }
}

//str_pad — 使用另一个字符串填充字符串为指定长度
/*
参数 

input
输入字符串。

pad_length
如果 pad_length 的值是负数，小于或者等于输入字符串的长度，不会发生任何填充，并会返回 input 。

pad_string
	Note:
	如果填充字符的长度不能被 pad_string 整除，那么 pad_string 可能会被缩短。
pad_type
可选的 pad_type 参数的可能值为 STR_PAD_RIGHT，STR_PAD_LEFT 或 STR_PAD_BOTH。如果没有指定 pad_type，则假定它是 STR_PAD_RIGHT。

返回值 

返回填充后的字符串。
*/