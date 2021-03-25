<?php
//提示：请核实PHP.INI是否开启OpenSSL，否则无法进行RSA加密
namespace tool;
class Rsa{
    //private_key 私钥;
    public $private_key = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAPnChWoVra6YUr+h
JjINLwNWFLgvg0eTBmIBsRyEHiupZkqZeiWq7n9vUQhohUCrz+PWuEIAe9EO4ezb
szqURTx8F9ByPthUohsgaczWr0eJNvj8lnoPOrWJyK428I5iGjJaTtvOzUeR2H73
wNo8ZD1C5Z6EYVuW/V4zU3Jtiu6DAgMBAAECgYEAnJbR0LYww3NrBgxCB0VuwVe5
+9SGKVzLtqy631cSF2vI32KkS3OEvk8Lbgsh6G8QExfvRCpLdsIu8bK5BzQox0ss
V2DjRESVIBwyXRBMakPN1Vo8TbrNgKa26vSsRGhFZICKdc0Yr0kA3SUJoG9ar/G8
4CWdC3pJj4VAENCLCKECQQD/Oq4mUHIfyk+Y3oe1UWYdOmwRhH6ljHZuSFGPcRJc
giingquTyE2p0qO8oLJQGxptfNeCsWtDNExrZPKqGggTAkEA+oOcymdmAHMCFKWM
U7SHdcSB2kgRdiI8EM55Z1+gxxklKygCc9ZjqA+WWThCImIfzyrl4ZyxTHiBcWgf
M4Ut0QJBALlCDLp+1ffBT7l0fSjdZrN8fojQlWTw6d3u3FS0DFHdoEjGjmf8knLc
FEGMmyGOKsaiQYP56BOl2HpzkbhqoMUCQEfCbaZZChH039K0PUc4/liQyrWRUVcq
pVQXIRWogfCmVkxPcKxn7DIXDPVPtToOK5h3bFQ9Q1hpaILo1Y83hhECQQDZgifA
DfahURIokdVRc/s/8jiyj7ap3NIKmvTNJPuoT+nekRYrruoVmz1ePi0QZ/R98VwB
vhZlAOAGx1RsAO+D
-----END PRIVATE KEY-----';

    //public_key 公钥
    public $public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQD5woVqFa2umFK/oSYyDS8DVhS4
L4NHkwZiAbEchB4rqWZKmXolqu5/b1EIaIVAq8/j1rhCAHvRDuHs27M6lEU8fBfQ
cj7YVKIbIGnM1q9HiTb4/JZ6Dzq1iciuNvCOYhoyWk7bzs1Hkdh+98DaPGQ9QuWe
hGFblv1eM1NybYrugwIDAQAB
-----END PUBLIC KEY-----';

    public $pi_key;
    public $pu_key;

    //判断公钥和私钥是否可用
    public function __construct()
    {
        $this->pi_key =  openssl_pkey_get_private($this->private_key); //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $this->pu_key = openssl_pkey_get_public($this->public_key); //这个函数可用来判断公钥是否是可用的
    }

    //公钥加密
    public function PublicEncrypt($data){
        //openssl_public_encrypt($data,$encrypted,$this->pu_key);/ /公钥加密
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->pu_key);
            $crypto .= $encryptData;
        }
        $encrypted = $this->urlsafe_b64encode($crypto);
        return $encrypted;
    }

    //公钥解密  私钥加密的内容通过公钥解密
    public function PublicDecrypt($encrypted){
        //$encrypted = $this->urlsafe_b64decode($encrypted);
        $crypto = '';
        foreach (str_split($this->urlsafe_b64decode($encrypted), 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $this->pu_key);
            $crypto .= $decryptData;
        }
        //openssl_public_decrypt($encrypted,$decrypted,$this->pu_key); //私钥加密的内容通过公钥可用解密出来
        return $crypto;
    }

    //私钥加密
    public function PrivateEncrypt($data){
        //openssl_private_encrypt($data,$encrypted,$this->pi_key);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $this->pi_key);
            $crypto .= $encryptData;
        }
        $encrypted = $this->urlsafe_b64encode($crypto); //加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    //私钥解密
    public function PrivateDecrypt($encrypted){
        $crypto = '';
        foreach (str_split($this->urlsafe_b64decode($encrypted), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $this->pi_key);
            $crypto .= $decryptData;
        }
        //$encrypted = $this->urlsafe_b64decode($encrypted);
        //openssl_private_decrypt($encrypted,$decrypted,$this->pi_key);
        return $crypto;
    }

    //加密码时把特殊符号替换成URL可以带的内容
    function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        //$data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    //解密码时把转换后的符号替换特殊符号
    function urlsafe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}