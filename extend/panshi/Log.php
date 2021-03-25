<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 11:36
 */

namespace panshi;


class Log
{
    public static $debug = false;//是否开启打印

    public static function println($str)
    {
        if (!self::$debug)return;
        echo $str,PHP_EOL;
    }
}