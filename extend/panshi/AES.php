<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 10:20
 */

namespace panshi;


class AES
{

    public static function getAutoCreateAESKey()
    {
        $length = 16;
        return base64_encode(openssl_random_pseudo_bytes($length));
    }

    public static function encrypt($data, $aesKey)
    {
        $cipher = 'AES-128-ECB';
        return openssl_encrypt($data, $cipher, base64_decode($aesKey), OPENSSL_RAW_DATA);
    }

    public static function decrypt($data, $aesKey)
    {
        $cipher = 'AES-128-ECB';
        return openssl_decrypt($data, $cipher, base64_decode($aesKey), OPENSSL_RAW_DATA);
    }
}