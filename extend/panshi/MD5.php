<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 11:16
 */

namespace panshi;


class MD5
{
    public static function sign($text, $key)
    {
        return md5($text.$key);
    }

    public static function verify($text, $sign, $key)
    {
        $text = $text.$key;
        Log::println("签名串：".$text);
        $mysign = md5($text.$key);
        Log::println("结果：". $mysign);

        return strcasecmp($mysign, base64_decode($sign)) === 0;
    }
}