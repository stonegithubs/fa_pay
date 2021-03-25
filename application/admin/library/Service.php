<?php

namespace app\admin\library;

use app\admin\model\PayNotify;
use app\admin\model\PayOrder;
use app\common\model\Log;
use fast\Http;
use think\Exception;
use think\Hook;
use think\Loader;
use app\admin\model\Admin;
use app\admin\model\PayOrder as Order;
/**
 * 订单服务类
 *
 * @package addons\pay\library
 */
class Service
{

    /**
     * 提交订单并跳转到支付页
     *
     * @param float $price 金额,单位元,请保留两位小数
     * @param string $out_order_id 订单号,订单模块的唯一订单号
     * @param string $type 类型,可使用wechat或alipay,默认为wechat
     * @param int $product_id 商品ID,为空将根据金额自动匹配
     * @param string $notifyurl 回调URL,为空将触发addons_pay_notify事件
     * @param string $returnurl 支付成功返回URL,支付成功后返回的页面,为空将不会返回
     * @param string $extend 扩展数据,在notifyurl中将会原样返回
     */

    public static function submitOrder($price, $out_order_id, $type = 'wechat', $product_id = 0, $notifyurl = '', $returnurl = '', $extend = '')
    {
        $config = get_addon_config('pay');
        $params = [
            'price'        => $price,
            'out_order_id' => $out_order_id,
            'type'         => $type,
            'product_id'   => $product_id,
            'notifyurl'    => $notifyurl,
            'returnurl'    => $returnurl,
            'extend'       => $extend,
            'app_id'       => '123456',
            'player_uuid'       => 'player-001',
        ];
        $params = array_filter($params);
        $params['sign'] = md5(md5(implode('', $params)) . '654321');
        $params['format'] = 'html';
        throw new HttpResponseException(redirect(addon_url("pay/api/create", $params)));
    }

    /**
     * 创建订单并获取订单信息，可在自定义二维码展示时使用
     *
     * @param float $price 金额,单位元,请保留两位小数
     * @param string $out_order_id 订单号,订单模块的唯一订单号
     * @param string $type 类型,可使用wechat或alipay,默认为wechat
     * @param int $product_id 商品ID,为空将根据金额自动匹配
     * @param string $notifyurl 回调URL,为空将不进行通知
     * @param string $returnurl 支付成功返回URL,支付成功后返回的页面,为空将不会返回
     * @param string $extend 扩展数据,在notifyurl中将会原样返回
     *
     * @return array|false|null|\PDOStatement|string|\think\Model|static
     * @throws OrderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function createOrder($price, $out_order_id, $type = 'alipay',  $notifyurl = '', $returnurl = '', $extend = '',$player_uuid='',$app_id='',$app_name='',$account_id='',$account='',$admin_id = '',$adminname = '')
    {
        $config = get_addon_config('pay');
        $now_time = time();


        //如果未传递则从默认配置中获取
        $notifyurl = $notifyurl ? $notifyurl : $config['notifyurl'];
        $returnurl = $returnurl ? $returnurl : $config['returnurl'];


        //支付方式
        $type = $type ? $type : 'alipay';

        $price = sprintf("%.2f", $price);


        $paidOrder = Order::where('status', 'not in', ['inprogress', 'expired'])->where('out_order_id', $out_order_id)->where('admin_id', $admin_id)->where('app_id', $app_id)->find();
        if ($paidOrder) {
            throw new OrderException("订单已支付成功,请勿重复支付");
        }

        $order = Order::where('out_order_id', $out_order_id)->where('type', $type)->where('admin_id', $admin_id)->where('app_id', $app_id)->where('expiretime', '>', time())->where('status', 'inprogress')->find();
        if ($order) {
            return $order;
        }


        $data = [
            'out_order_id' => $out_order_id,
            'price'        => $price,
            'realprice'    => $price,
            'notifyurl'    => $notifyurl,
            'returnurl'    => $returnurl,
            'type'         => $type,
            'title'        => $app_name,
            'expiretime'   => time() + $config['expireseconds'],
            'extend'       => $extend,
            'admin_id'     =>$admin_id,
            'app_id'       =>$app_id,
            'player_uuid'  =>$player_uuid,
            'admin_name'  =>$adminname,
            'account_id'  =>$account_id,
            'account'  =>$account,
        ];

        $model = Order::create($data);
        $order = Order::get($model->id);
        //注册一个行为
        Hook::listen('addons_pay_created', $order);
        return $order;
    }

    /**
     * 处理订单
     *
     */
    public static function handleOrder($order_id,$out_order_id = null,$sys_order=null)
    {

        //TestM::add_log('service/handleOrder','start');
        if ($order_id) {
            $OrderM = new PayOrder();

            $myOrder = array();
            $myOrder['status'] = 2;//已经支付
            $myOrder['paydate'] = date('Y-m-d H:i:s');
            $myOrder['paytime'] = time();


            $where = array();
            $where['id'] = $order_id;
            $where['status'] = array('in','0,1');
            $res = $OrderM->where($where)->update($myOrder);
            if ($res)
            {
                $result = \app\admin\library\Service::notify($order_id);
                return true;
            }

        }

        return false;


    }

    /**
     * 订单通知
     *
     * @param $order
     * @return bool
     */
    public static function notify($order)
    {
        $OrderM = new PayOrder();
        $NotifyM = new PayNotify();
        if (is_numeric($order)) {
            $order = $OrderM::get($order);
            if (!$order) {
                return false;
            }
        }
        //如果有传递notifyurl则优先根据notifyurl的返回结果进行判断
        if ($order['notifyurl']) {
            
            $params = [
                'appid'       => $order['appid'],
                'paytype'       => $order['paytype'],
                'title'       => $order['title'],
                'out_order_id' => $order['out_order_id'],
                'sys_order_id' => $order['sys_order_id'],
                'realprice'    => $order['realprice'],
                'paytime'      => $order['paytime'],
                'paydate'      => $order['paydate'],
                'extend'       => $order['extend'],
            ];

            $admin_info = Admin::get($order['admin_id']);
            $appsecret = $admin_info['appsecret'];

            $logM = new Log();
            $logM->addLog(self::build_sign_str($params).$appsecret,'sercer/notify');

            $params['sign'] = md5(self::build_sign_str($params).$appsecret);
            $status = 3;//通知失败

            $notify = $NotifyM::create(['order_id' => $order['id'],'out_order_id' => $order['out_order_id'], 'url' => $order['notifyurl'], 'params' => http_build_query($params), 'status' => 'created','sys_order_id' =>$order['sys_order_id']]);
            //通过http发送回调通知

            $order['notifyurl'] = stripslashes($order['notifyurl']);

            $result = http::sendRequest($order['notifyurl'], $params, 'POST');


            if ($result['ret'] && $result['msg'] == 'success') {
                $status = 4;//通知成功
                $notify->status = 'success';
            } else {
                $notify->status = 'failure';
            }
            $notify->response = $result['msg'];
            $notify->save();
        } else {
            $result = Hook::listen('addons_pay_notify', $order);
            $status = $result ? 4 : 3;
            //TestM::add_log('servive/notify',$status);
        }
        $order->status = $status;
        $order->save();

        return $status == 4 ? true : false;
    }

    /**
     * 获取二维码的信息
     *
     * @param $url
     * @return array
     */
    public static function getQrcodeData($url)
    {
        $origin = $url;
        $config = get_addon_config('pay');
        $client = new AipOcr($config['ocr_app_id'], $config['ocr_apikey'], $config['ocr_secretkey']);

        //如果为远程模式或网络图片
        if ($config['ocr_type'] == 'remote' || preg_match("/^(http|https)(.*)/", $url)) {
            // 调用通用文字识别, 图片参数为远程url图片
            $url = cdnurl($url, true);
            $result = Http::get($url);
            if ($result) {
                $image = $result;
            } else {
                return [];
            }
        } else {
            $file = ROOT_PATH  . $url;
            if (is_file($file)) {
                $image = file_get_contents($file);
            } else {
                return [];
            }
        }

        $type = 'wechat';
        $price = 0;
        $text = '';
        $result = $client->basicGeneral($image);
        if (isset($result['words_result_num']) && isset($result['words_result'])) {
            foreach ($result['words_result'] as $index => $item) {
                if (isset($item['words'])) {
                    if (stripos($item['words'], "支付宝") !== false || stripos($item['words'], "alipay") !== false) {
                        $type = 'alipay';
                    }
                    if (stripos($item['words'], "微信") !== false || stripos($item['words'], "wechat") !== false) {
                        $type = 'wechat';
                    }

                    if (stripos($item['words'], "￥") !== false) {
                        $price = str_replace('￥', '', $item['words']);
                        if (stripos($price, '.') === false && strlen($price) >= 3) {
                            $price = substr_replace($price, "." . substr($price, -2), -2);
                        }
                    }
                }
            }
        }

        if ($config['qrcode_type'] == 'local') {
            require_once ADDON_PATH . 'pay' . DS . 'library' . DS . 'qrdecode' . DS . 'Common' . DS . "customFunctions.php";
            Loader::addNamespace('Zxing', ADDON_PATH . 'pay' . DS . 'library' . DS . 'qrdecode');

            $qrcode = new \Zxing\QrReader($image, \Zxing\QrReader::SOURCE_TYPE_BLOB);
            $text = $qrcode->text();
        } else if ($config['qrcode_type'] == 'oschina') {
            $multipart = [
                [
                    'name'     => 'upload_ctn',
                    'contents' => 'on'
                ],
                [
                    'name'     => 'url',
                    'contents' => '',
                ],
                [
                    'name'     => 'qrcode',
                    'contents' => $image,
                    'filename' => 'qrcode',
                ]
            ];
            try {
                $client = new \GuzzleHttp\Client();
                $res = $client->request('POST', "http://tool.oschina.net/action/qrcode/decode", [
                    'multipart' => $multipart,
                    'headers'   => ['Accept-Encoding' => 'gzip'],
                ]);
                $content = $res->getBody();
                $ret = json_decode($content, true);
                $text = isset($ret[0]['text']) ? $ret[0]['text'] : $text;
            } catch (\GuzzleHttp\Exception\ClientException $e) {

            }
        } else if ($config['qrcode_type'] == 'caoliao') {
            $url = cdnurl($url, true);
            $result = Http::post("https://cli.im/Api/Browser/deqr", ['data' => $url]);
            if ($result) {
                $json = json_decode($result, TRUE);
                if (isset($json['status']) && $json['status'] == 1 && isset($json['data']['RawData'])) {
                    $text = $json['data']['RawData'];
                }
            }
        }

        return ['type' => $type, 'realprice' => $price, 'image' => $origin, 'url' => $text];

    }

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

}