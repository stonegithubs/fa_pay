<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/9/3
 * Time: 11:12
 */

namespace panshi;


class RSA
{

    public static function encryptByPublicKey($data, $publicKey)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        if (!openssl_public_encrypt($data, $crypted, $res)){
            throw new \RuntimeException("公钥加密失败");
        }

        return $crypted;
    }

    public static function sign($text, $privateKey)
    {
        //构造私钥
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if (!openssl_sign($text, $sign, $res)){
            throw new \RuntimeException("私钥签名失败");
        }
        return base64_encode($sign);
    }

    public static function decryptByPrivateKey($encryptedData, $privateKey)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if (!openssl_private_decrypt($encryptedData, $decrypted, $res)){
            throw new \RuntimeException("私钥解密失败");
        }

        return $decrypted;
    }

    public static function verify($text, $sign, $publicKey)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        return openssl_verify($text, base64_decode($sign), $res)===1;
    }
}