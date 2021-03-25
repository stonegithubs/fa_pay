<?php

$pay = new VivaPay;
$pay->rmbPrice(); //商户入金数字币报价（人民币）
$pay -> pay();

class VivaPay{
    private $app_id = "42474946"; //商户帐号ID <由 FastPay 分配,在商户后台--账户中心--商户信息 中获取>
    private $mch_id = "822352538"; //商户号 <由 FastPay 分配,在商户后台--账户中心--商户信息 中获取>
    private $app_key ='7038DD559EEBC5B1CC3A2D5064758788';
    private $into_url = "http://merchant-api.katepay.com/api/recharge/check/v2";  //入金地址
	private $rmb_price_url = "http://merchant-api.katepay.com/api/recharge/convert/v1";
	
	 //商户入金数字币报价（人民币）
    public function rmbPrice(){
       $param = array(
           'p1' => $this->mch_id,
           'timestamp' => time()
       );
        $rlt = $this->httpGET($this->rmb_price_url,$param);
        var_dump($rlt);

    }

	//入金代码
    public function pay(){
       $param = array(
           'p1' => 100,
           'p2' => $this->mch_id,
           'p3' => time().rand(1,100),
           'timestamp' => time()
       );
        $rlt = $this->httpGET($this->into_url,$param);
        var_dump($rlt);

    }

   public function mkSign($data){
       if(!is_array($data)){
           return false;
       }
       $str = '';
       $flag = false;
       foreach ($data as $v){
           if(empty($v)){
               continue;
           }
           $str.=$v.'&';
       }
       $str = substr($str,0,strlen($str)-1);
       return $str;
   }

    /**  PHP 的 HMAC_SHA1算法 实现
     * @param $str
     * @param $key
     * @return string
     */
    function getSignature($str, $key) {
        $signature = "";
        if (function_exists('hash_hmac')) {
            $signature = bin2hex(hash_hmac("sha1", $str, $key, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                'H*', $hashfunc(
                    ($key ^ $opad) . pack(
                        'H*', $hashfunc(
                            ($key ^ $ipad) . $str
                        )
                    )
                )
            );
            $signature = bin2hex($hmac);
        }
        return $signature;
    }
    /**
     * 远程获取数据，GET模式
     */
    function httpGET($url,$data) {
        $url .= '?p1='.$data['p1'].'&p2='.$data['p2'].'&p3='.$data['p3'].'&timestamp='.$data['timestamp'];
        echo 'url:'.$url."</br>";    //请求地址和参数：http://merchant-api.katepay.com/api/recharge/check/v2?p1=100&p2=11082429&p3=1553155910&timestamp=1553155910

        $str = $this->mkSign($data);
        echo 'str:'.$str."</br>";   //加密前字符串：100&11082429&1553155910&1553155910
        if(empty($str)){
            return false;
        }
        $sign = strtoupper($this->getSignature($str,$this->app_key));  //加密后(大写)：5F51F8065B325EC3491526612CB2A47B84E5E10B
        echo 'sign:'.$sign."</br>";
        $headers = array(
            'content-type:application/json',
            'access_key:'.$sign,
            'app_id:'.$this->app_id
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }


}