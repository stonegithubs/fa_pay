<?php

namespace addons\epay\controller;

use addons\epay\library\Service;
use app\admin\model\PayOrder;
use app\common\model\Log;
use think\addons\Controller;
use Exception;
use KTools\KTools;
use tool\Request as ToolReq;
use app\admin\model\pay\Type;
use think\exception\DbException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use app\admin\model\Admin;


//http://m.pollod.cn/m

class Apiv15 extends Controller
{

    protected $layout = 'default';

    protected $config = [];

    protected $paytype = 'alipay_v15';//支付标识

    protected $token = null;

    public function _initialize()
    {
        parent::_initialize();


    }


 /*   public function index()
    {
        $apiUrl = 'http://118.31.109.125/nbpay/pay/getway/';
        $randomString = date('YmdHis').time();
        $secretKey = 'hiegu98kjgo45';

        $postData = array();
        $postData['sign'] =md5( md5($apiUrl.$randomString).$secretKey);
        $postData['businessId'] = '1133719903038963712';
        $postData['randomString'] = $randomString;
        $postData['businessOrderId'] = 'E'.date('YmdHis').time();
        $postData['description'] = 'goods';
        $postData['extrat'] = 'good goods';
        $postData['money'] = 1;
        $postData['payType'] = 'alipay';
        $postData['notifyUrl'] = 'http://www.xhd9.com/api/index';


        $res = $this->request_post($apiUrl,$postData);
        $resObj = json_decode($res,true);
        if (isset($resObj['status']) && $resObj['status']==0 && isset($resObj['alipayUrl']))
        {
            $order = array();
            $order['qrcode'] = $resObj['qrcode'];
            $order['alipayUrl'] = $resObj['alipayUrl'];
            $order['out_order_id'] = $postData['businessOrderId'];
            $this->view->assign("order", $order);
            return $this->view->fetch('pay');
        }else{
            echo $res;
        }

    }*/

    public function submit()
    {

        $out_trade_no = $this->request->request("out_trade_no");
        $amount = $this->request->request('amount');
        $title = $this->request->request("title");

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        $paytype = $this->paytype;
        try {
            $payTypeInfo = $PayTypeM->where('type', $paytype)->where('status',1)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用');
        }
        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        $this->config = $payTypeInfo['config'];


        $apiUrl = $this->config['apiUrl'];
        $randomString = 'eKGp1aBqyS7unmBExS47zcQpCJrcJx3N';
        $secretKey = $this->config['secretKey'];



        $data = [
            'appid' => $this->config['appid'],
            'amount' => $amount,
            'method' => "2",
            'out_trade_no' => $out_trade_no,
            'nonce_str' => $randomString,
        ];
        $appsecret = $secretKey;
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $appsecret));
        $data['notifyurl'] =$this->config['sys_notifyurl'];
        $data['returnurl'] =$this->config['sys_returnurl'];
        $result = $this->httpRequest($apiUrl,'POST',json_encode($data));
        
//      	$logM = new Log();
//		$logM->addLog($result,$this->paytype.'/httpRequest');
      
        $result = json_decode($result,true);
        if($result['code'] == 1){
            header("location:".$result['payUrl']);exit;
        }else{
            $this->error('支付通道异常');
        }

    }


    /**
     * 支付成功回调
     */
    public function notifyx()
    {

        $logM = new Log();
//        $logM->addLog(json_encode($this->request->param()),$this->paytype.'/notifyx');
//        $logM->addLog( $_SERVER['REMOTE_ADDR'],$this->paytype.'/notifyx/ip');

        //验证支付方式是否可用
        $PayTypeM  = new Type();
        $payTypeInfo = false;
        try {
            $payTypeInfo = $PayTypeM->where('type', $this->paytype)->find();
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        if (!$payTypeInfo)
        {
            $this->error('支付通道不可用');
        }
        $payTypeInfo['config'] = json_decode($payTypeInfo['config'],true);
        $this->config = $payTypeInfo['config'];
        $secretKey = $this->config['secretKey'];


        /*$post_data = file_get_contents('php://input');
        $data = json_decode($post_data,true);
        // $data['appid'] = $_POST['appid'];
        // $data['nonce_str'] = $_POST['nonce_str'];
        // $data['status'] = $_POST['status'];
        // $data['sign'] = $_POST['sign'];
        // $data['amount'] = $_POST['amount'];
        // $data['sdpayno'] = $_POST['sdpayno'];
        // $data['out_trade_no'] = $_POST['out_trade_no'];
        $appsecret = $secretKey;
        $sign = $this->getSign($data,$appsecret);
        if($sign == $data['sign']){
            //成功后的业务逻辑
        }*/

        $post_data = file_get_contents('php://input');
        $payData = json_decode($post_data,true);
//        $logM->addLog($post_data,$this->paytype.'/notifyx/php://input');


        if (isset($payData['out_trade_no']))
        {
            if (1)
            {
                try {

//                    $logM->addLog('ok',$this->paytype.'/notifyx');
                    $OrderM = new PayOrder();

                    //修改订单状态
                    $out_trade_no = $payData['out_trade_no'];
//                    $logM->addLog($payData['out_trade_no'],$this->paytype.'/notifyx/out_trade_no');

                    $myOrder = array();
                    $myOrder['status'] = 2;//已经支付
                    $myOrder['paytime'] = time();
                    $myOrder['paydate'] = date('Y-m-d H:i:s',$myOrder['paytime']);
//                    $myOrder['amount'] = $payData['amount'] / 100;
                    $where = array();
                    $where['out_order_id'] = $out_trade_no;
                    $where['status'] = array('in','0,1');
                    $res = $OrderM->where($where)->update($myOrder);
//                    $logM->addLog($res,$this->paytype.'/notifyx/res');
                    //订单详情
                    $where = array();
                    $where['out_order_id'] = $out_trade_no;
                    $orderInfo = $OrderM->where($where)->find();


                    //下发商户通知
                    $result = \app\admin\library\Service::notify($orderInfo['id']);

                    $APiC = new Api();
                    //扣除费率及记账
                    $APiC->dealServiceCharge($orderInfo);



                    //你可以在此编写订单逻辑
                } catch (Exception $e) {

                }

            }
            exit("ok");
        }else{
            $this->error('error');
        }
    }



    function getSign($arr,$appsecret){
        //去除数组中的空值
        $arr = array_filter($arr);
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        //按照键名字典排序
        ksort($arr);
        //生成URL格式的字符串
        $str = http_build_query($arr)."&key=".$appsecret;
        $str = $this->arrToUrl($str);
        return  strtoupper(md5($str));
    }

    //URL解码为中文
    function arrToUrl($str){
        return urldecode($str);
    }

    /**
     * @param $url
     * @param string $method
     * @param null $postfields
     * @param array $headers
     * @return mixed
     */
    public function httpRequest($url, $method="GET", $postfields = null, $headers = array()) {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if($ssl){
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        curl_close($ci);
        return $response;

    }

    public function unicode_decode($name)
    {
        // 转换编码，将Unicode编码转换成可以浏览的utf-8编码
        $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
        preg_match_all($pattern, $name, $matches);
        if (!empty($matches))
        {
            $name = '';
            for ($j = 0; $j < count($matches[0]); $j++)
            {
                $str = $matches[0][$j];
                if (strpos($str, '\\u') === 0)
                {
                    $code = base_convert(substr($str, 2, 2), 16, 10);
                    $code2 = base_convert(substr($str, 4), 16, 10);
                    $c = chr($code).chr($code2);
                    $c = iconv('UCS-2', 'UTF-8', $c);
                    $name .= $c;
                }
                else
                {
                    $name .= $str;
                }
            }
        }
        return $name;
    }
}
