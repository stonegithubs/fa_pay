<?php
/**
 * Created by PhpStorm.
 * User: M
 * Date: 2019/8/19 0019
 * Time: 16:41
 */

namespace app\Tools\AllinPay;

class AppUtil
{
    /**
     * 将参数数组签名
     */
    public static function SignArray(array $array, $appkey)
    {
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        //ksort($array);
        $blankStr = AppUtil::ToUrlParams($array);
        $sign = md5($blankStr);
        return $sign;
    }

    public static function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v) {
            if ($v != "" && !is_array($v)) {
                $buff .= $v;
            }
        }
        return $buff;
    }

    /**
     * 校验签名
     * @param array $array 参数
     * @param string $appkey appkey
     * @return bool
     */
    public static function ValidSign(array $array, $appkey)
    {
        $sign = $array['sign'];
        unset($array['sign']);
        $array['key'] = $appkey;
        $mySign = AppUtil::SignArray($array, $appkey);
        return strtolower($sign) == strtolower($mySign);
    }
}