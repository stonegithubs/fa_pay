<?php
/*
签名算法
*/
function sign ($key_id, $array)
    {
            $data = md5(sprintf("%.2f", $array['amount']) . $array['out_trade_no']);
            $key[] ="";
            $box[] ="";
            $pwd_length = strlen($key_id);
            $data_length = strlen($data);
            for ($i = 0; $i < 256; $i++)
            {
                $key[$i] = ord($key_id[$i % $pwd_length]);
                $box[$i] = $i;
            }
            for ($j = $i = 0; $i < 256; $i++)
            {
                $j = ($j + $box[$i] + $key[$i]) % 256;
                $tmp = $box[$i];
                $box[$i] = $box[$j];
                $box[$j] = $tmp;
            }
            for ($a = $j = $i = 0; $i < $data_length; $i++)
            {
                $a = ($a + 1) % 256;
                $j = ($j + $box[$a]) % 256;

                $tmp = $box[$a];
                $box[$a] = $box[$j];
                $box[$j] = $tmp;

                $k = $box[(($box[$a] + $box[$j]) % 256)];
                $cipher .= chr(ord($data[$i]) ^ $k);
            }
            return md5($cipher);
    }
	/*
	生成13位时间戳
	*/
	function getMillisecond() {

    list($t1, $t2) = explode(' ', microtime());

    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);

}
/*
发送请求
*/
 function setHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }
	/*
	提交内容
	*/
	$mch_id='1';//商户id
	$s_key='DFC86E5F1F55CF';//商户秘钥
	$url='http://39.104.138.172/gateway/index/checkpoint.do';//提交地址
	$orderid='2019'.getMillisecond();
	$amount=1;
	$args = [
         'account_id'=>$mch_id,
         'content_type'=>'text',
         'thoroughfare'=>'alipay_auto',
         'out_trade_no'=>$orderid,
         'sign'=>sign($s_key, ['amount'=>$amount,'out_trade_no'=>$orderid]),
         'robin'=>2,
         'callback_url'=>'http://baidu.com',
         'success_url'=>'http://baidu.com',
         'error_url'=>'http://baidu.com',
         'amount'=>$amount,
         'type'=>2,
         'keyId'=>''];
		
        setHtml($url, $args);
		
		
	
?>
