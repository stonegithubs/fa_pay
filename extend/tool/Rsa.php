<?php

/**
 * RSA加密解密算法
 * Class Rsa
 * @package tool
 */
namespace tool;
class Rsa
{

    /**
     * 获取pem格式的公钥
     * @param $public_key 公钥文件路径或者字符串
     * @return bool|mixed|string
     */
    public static function public_key($public_key)
    {
        try{
            // 先判断是否是文件
            $suffix = pathinfo($public_key,PATHINFO_EXTENSION);
            if(!empty($suffix) && is_file($public_key)){
                $public_key = file_get_contents($public_key);
            }
            if(false === strpos($public_key, '-----')){
                $public_key = str_replace("\n", "", $public_key);
                $public_key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($public_key, 64, "\n") . "-----END PUBLIC KEY-----";
            }
        }catch (\Exception $e){
            $public_key = '';
        }

        return $public_key;
    }

    /**
     * 获取pem格式的私钥
     * @param $private_key 私钥文件路径或者字符串
     * @return bool|mixed|string
     */
    public static function private_key($private_key, $key_password='')
    {
        try{
            // 先判断是否是文件
            $suffix = pathinfo($private_key,PATHINFO_EXTENSION);
            if(!empty($suffix) && is_file($private_key)){
                $private_key = file_get_contents($private_key);
            }
            if(false === strpos($private_key, '-----')){
                $private_key = str_replace("\n", "", $private_key);
                if(empty($key_password)){
                    $private_key = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($private_key, 64, "\n") . "-----END PRIVATE KEY-----";
                }else{
                    $private_key = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" . chunk_split($private_key, 64, "\n") . "-----END ENCRYPTED PRIVATE KEY-----";
                }
            }
        }catch (\Exception $e){
            $private_key = '';
        }

        return $private_key;
    }

    /**
     * RSA公钥加密
     * @param $decrypted 待加密字符串
     * @param $public_key 公钥
     * @return bool|string
     */
    public static function public_encrypt($decrypted, $public_key)
    {

        try{
            $public_key = self::public_key($public_key);

            $publicKey = openssl_pkey_get_public($public_key); //这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
            if(!$publicKey) return false;

            $decrypted = str_split($decrypted, 117);
            $encrypted = '';
            foreach ($decrypted as $decrypt){
                $encrypt = '';
                openssl_public_encrypt($decrypt, $encrypt, $publicKey);//公钥加密
                $encrypted .= $encrypt; //加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
            }
            $encrypted = base64_encode($encrypted);
            openssl_free_key($publicKey);
            unset($decrypted, $public_key, $publicKey, $decrypt, $encrypt);
        }catch (\Exception $e){
            $encrypted = '';
        }

        return $encrypted;
    }

    /**
     * RSA私钥加密
     * @param $decrypted 待加密字符串
     * @param $private_key 私钥
     * @return bool|string
     */
    public static function private_encrypt($decrypted, $private_key, $key_password='')
    {

        try {
            $private_key = self::private_key($private_key, $key_password);

            $privateKey = openssl_pkey_get_private($private_key, $key_password); //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
            if (!$privateKey) return false;

            $decrypted = str_split($decrypted, 117);
            $encrypted = '';
            foreach ($decrypted as $decrypt) {
                $encrypt = '';
                openssl_private_encrypt($decrypt, $encrypt, $privateKey);//公钥加密
                $encrypted .= $encrypt; //加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
            }
            $encrypted = base64_encode($encrypted);
            openssl_free_key($privateKey);
            unset($decrypted, $private_key, $privateKey, $decrypt, $encrypt);
        }catch (\Exception $e){
            $encrypted = '';
        }

        return $encrypted;
    }

    /**
     * RSA公钥解密
     * @param $encrypted 待解密密文
     * @param $public_key 公钥
     * @param string $key_password 证书密码
     * @return bool|string
     */
    public static function public_decrypt($encrypted, $public_key)
    {

        try{
            $public_key = self::public_key($public_key);

            $publicKey = openssl_pkey_get_public($public_key); //这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
            if(!$publicKey) return false;

            $encrypted = str_split(base64_decode($encrypted), 128);
            $decrypted = '';
            foreach ($encrypted as $encrypt){
                $decrypt = '';
                openssl_public_decrypt($encrypt, $decrypt, $publicKey);//私钥解密
                $decrypted .= $decrypt;
            }
            openssl_free_key($publicKey);
            unset($encrypted, $public_key, $publicKey, $encrypt, $decrypt);
        }catch (\Exception $e){
            $decrypted = '';
        }

        return $decrypted;
    }

    /**
     * RSA私钥解密
     * @param $encrypted 待解密密文
     * @param $private_key 私钥
     * @param string $key_password 证书密码
     * @return bool|string
     */
    public static function private_decrypt($encrypted, $private_key, $key_password='')
    {
        try{
            $private_key = self::private_key($private_key, $key_password);

            $privateKey = openssl_pkey_get_private($private_key, $key_password); //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
            if(!$privateKey) return false;

            $encrypted = str_split(base64_decode($encrypted), 128);

            $decrypted = '';
            foreach ($encrypted as $encrypt){
                $decrypt = '';
                openssl_private_decrypt($encrypt, $decrypt, $privateKey);//私钥解密
                $decrypted .= $decrypt;
            }
            openssl_free_key($privateKey);
            unset($encrypted, $private_key, $privateKey, $encrypt, $decrypt);
        }catch (\Exception $e){
            $decrypted = '';
        }

        return $decrypted;
    }

    /**
     * 私钥生成签名
     * @param $string 待签名字符串
     * @param $private_key 私钥
     * @param string $key_password 证书密码
     * @return bool|string
     */
    public static function sign($string, $private_key, $key_password='')
    {

        try{
            $private_key = self::private_key($private_key, $key_password);

            $privateKey = openssl_pkey_get_private($private_key, $key_password); //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
            if(!$privateKey) return false;

            openssl_sign($string, $sign, $privateKey);
            openssl_free_key($privateKey);
            $sign = base64_encode($sign);//最终的签名
            unset($string, $private_key, $key_password, $privateKey);
        }catch (\Exception $e){
            $sign = '';
        }

        return $sign;
    }

    /**
     * 公钥校验签名
     * @param $string 待签名字符串
     * @param $sign 签名
     * @param $public_key 公钥
     * @return bool
     */
    public static function verify($string, $sign, $public_key)
    {

        try{
            $public_key = self::public_key($public_key);

            $publicKey = openssl_pkey_get_public($public_key); //这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
            if(!$publicKey) return false;

            $sign = base64_decode($sign);//得到的签名
            $result = openssl_verify($string, $sign, $publicKey);
            openssl_free_key($publicKey);
            unset($string, $sign, $public_key, $publicKey);
        }catch (\Exception $e){
            $result = 0;
        }

        return $result === 1 ? true : false;
    }

}