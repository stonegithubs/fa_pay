<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 10:30
 */

namespace panshi;


use RuntimeException;

class MerchantUtil
{
    public const CHARSET = 'UTF-8';
    public const SIGNTYPE_MD5 = 'MD5';
    public const SIGNTYPE_RSA = 'RSA';

    public static function encryptDataByAES($paramStr, $aesKey)
    {
        $bytes = AES::encrypt($paramStr, $aesKey);
        return base64_encode($bytes);
    }

    public static function encryptDataByRSA($src, $publicKey)
    {
        $bytes = RSA::encryptByPublicKey($src, $publicKey);
        return base64_encode($bytes);
    }

    public static function sign($jsonStr, $key, $signType)
    {
        switch ($signType) {
            case 'MD5':
                $mysign = MD5::sign($jsonStr, $key);
                break;
            case 'RSA':
                $mysign = RSA::sign($jsonStr, $key);
                break;
            default:
                throw new RuntimeException("未知签名方式，丢弃");
        }

        return $mysign;
    }

    public static function decryptDataByRSA($src, $privateKey)
    {
        $decryptData = RSA::decryptByPrivateKey(base64_decode($src), $privateKey);
        return $decryptData;
    }

    public static function verify($text, $sign, $key, $signType)
    {
        switch ($signType) {
            case 'MD5':
                $isTrue = MD5::verify($text, $sign, $key);
                break;
            case 'RSA':
                $isTrue = RSA::verify($text, $sign, $key);
                break;
            default:
                throw new RuntimeException("未知签名方式，丢弃");
        }
        return $isTrue;
    }

    public static function decryptDataByAES($src, $aesKey)
    {
      return AES::decrypt(base64_decode($src), $aesKey);
    }
}