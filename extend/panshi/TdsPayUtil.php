<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 10:14
 */

namespace panshi;


class TdsPayUtil
{

    public static function getSignData(array $map)
    {
        $resStr = '';
        ksort($map, SORT_STRING);
        foreach ($map as $val) {
            $resStr .= urlencode($val);
        }

        //System.out.println("拼接的代签名数据：" + resStr);
        return $resStr;
    }

    public static function getShaSign($string)
    {
        return sha1($string);
    }
}