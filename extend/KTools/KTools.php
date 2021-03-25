<?php

namespace KTools;


use think\Db;
use think\Config;
use think\Session;
use think\Request;


class KTools
{

    /**
     * 数组转query
     * @param $data
     * @return string
     */
    public static function build_query($data){
        $str='';
        if(is_array($data)){
            ksort($data);
            foreach ($data as $key=>$value){
                $str .= (empty($str) ? '' : '&' ) . $key . '=' . (is_array($value) ? '(' .self::build_query($value). ')' : urldecode(str_replace('+', '%2B', $value)));
            }
        }
        return $str;
    }

    /**
     * 数组转query
     * @param $data
     * @return string
     */
    public static function build_query_cpay($data){
        $str='';
        if(is_array($data)){
            ksort($data);
            foreach ($data as $key=>$value){
                $str .= (empty($str) ? '' : '&' ) . $key . '=' . (is_array($value) ? '(' .self::build_query($value). ')' : str_replace('+', '%2B', $value));
            }
        }
        return $str;
    }


    /**
     * 参数值拼接
     * 数组转string
     * @param $data
     * @return string
     */
    public static function build_sign_str($data){
        $str='';
        if(is_array($data)){
            ksort($data);
            foreach ($data as $key=>$value){
                $str .= $value;
            }
        }
        return $str;
    }



    /**
     * 手机号码归属地-聚合数据
     *
     * @param string $phone 电话
     */
    public static function getPhoneAddr($phone)
    {
        $AppKey = '';
        $url = "http://apis.juhe.cn/mobile/get";
        $params = array(
            "phone" => $phone,//需要查询的手机号码
            "key" => $AppKey,//应用APPKEY(应用详细页查询)
        );
        $content = KTools::send_post_curl($url,$params);
        return $content;

    }

    /**
     * 自定义-可逆加密
     *
     * @param string $string 需要加密的字符串
     * @param string $key 加密密钥
     */
    public static function k_encrypt($string,$key='kelly')
    {
        //自带的加密函数
        $crypttext = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
        $encrypted =trim(self::safe_b64encode($crypttext));//对特殊字符进行处理
        return $encrypted;
    }

    /**
     * 自定义-可逆加密
     *
     * @param string $string 需要加密的字符串
     * @param string $key 加密密钥
     */
    public static function k_decrypt($string,$key='kelly')
    {
        $crypttexttb = self::safe_b64decode($string);//对特殊字符解析
        $decryptedtb = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($crypttexttb), MCRYPT_MODE_CBC, md5(md5($key))), "\0");//解密函数
        return $decryptedtb;
    }



    //处理特殊字符
    public static function safe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    //解析特殊字符
    public static function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    public static function send_post_curl_v1($url,$data = array()){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //执行并获取url地址的内容
        $output = curl_exec($ch);
        $header = curl_getinfo($ch);
        $http_code = $header['http_code'];
        //释放curl句柄
        curl_close($ch);
        if(200 != $http_code) {
            $log['output'] = $output;
            $log['requestData'] = $data;
            $log['curl_header'] = $header;
            //记日志哈
            return null;
        }
        return $output;
    }

    public static function send_post_curl($url,$data = array(),$json=false){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($json)
        {
            //类型为json
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8'
                )
            );
            $data = json_encode($data);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //执行并获取url地址的内容
        $output = curl_exec($ch);
        $header = curl_getinfo($ch);
        $http_code = $header['http_code'];
        //释放curl句柄
        curl_close($ch);
        if(200 != $http_code) {
            $log['output'] = $output;
            $log['requestData'] = $data;
            $log['curl_header'] = $header;
            //记日志哈
            return null;
        }
        return $output;
    }
  
 

}
