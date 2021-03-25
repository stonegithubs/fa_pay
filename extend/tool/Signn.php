<?php
namespace tool;
class Signn
{
	public $key;
	function __construct($md5key)
	{
		$this->key = $md5key;
	}

	/**
     * 验签
     * @param $params
     * @return bool
     */
    public function validateSign($params) {
        $stringA = $this->paramFilter($params);
        //echo $stringA;
        $sign = $this->md5Sign($stringA);

        if(!isset($params['sign']) || empty($params['sign']) || $params['sign'] != $sign) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $param
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    function paramFilter($param) {
        $para_filter = array();
        foreach ($param as $key => $val){
            if($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $param[$key];
        }
        return $this->argSort($para_filter);
    }

    /**
     * 对数组排序
     * @param $param
     * @return mixed 排序后的数组
     */
    function argSort($param) {
        ksort($param);
        reset($param);
        return $this->createLinkString($param);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para
     * @return bool|string 拼接完成以后的字符串
     */
    function createLinkString($para) {
        $arg  = "";
        foreach ($para as $key => $val) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,strlen($arg)-1);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }
        $arg = $arg.'&key='.$this->key;
        return $arg;
    }

    /**
     * 生成md5签名字符串
     * @param $preStr string 需要签名的字符串
     * @return string 签名结果
     */
    function md5Sign($preStr) {
        return strtoupper(md5($preStr));
    }
}