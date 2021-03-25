<?php
namespace panshi;

/**
 * curl http请求
 * Class CurlHttp
 * @package syslayer\net
 */
class CurlHttp
{
    const MODE_POST_HTTP = 1;
    const MODE_POST_HESSIAN = 2;

    protected $url = '';
    protected $set_url = '';
    protected $analysis_url = false;
    protected $query = array();
    protected $headers = array();

    protected $postdata = null;
    protected $postmode = 0;
    protected $analysis_post = false;


    protected $ssl = [
        CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_SSL_VERIFYHOST=>0,
        //CURLOPT_SSLVERSION=>0,
    ];

    protected $post = array();
    protected $files = array();

    //默认参数
    protected static $doption = array();

    //其他参数
    protected $option = array();

    public function __construct()
    {
        $this->option = self::$doption;
    }

    //以下是设置函数

    /**
     * 设置url
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->set_url = $url;
        $this->url = '';
        $this->analysis_url = false;
        return $this;
    }

    /**
     * 设置GET参数
     * @param array $query
     * @return $this
     */
    public function setQuery($query = array())
    {
        if (is_string($query)) {
            parse_str($query, $query);
        }

        if (!is_array($query)) {
            if ($this->query) {
                $this->query = array();
                $this->url = '';
                $this->analysis_url = false;
            }
            return $this;
        }

        if ($query == $this->query) {
            return $this;
        }

        $this->query = $query;
        $this->url = '';
        $this->analysis_url = false;

        return $this;
    }

    /**
     * 设置请求连接超时时间
     * @param $timeout
     * @return $this
     */
    public function setConnecttimeout($timeout)
    {
        $timeout = intval($timeout);
        if ($timeout > 0) {
            $this->option[CURLOPT_CONNECTTIMEOUT] = $timeout;
        } else {
            unset($this->option[CURLOPT_CONNECTTIMEOUT]);
        }
        return $this;
    }


    /**
     * 设置请求连接超时时间
     * @param $timeout_ms
     * @return $this
     */
    public function setConnecttimeoutms($timeout_ms)
    {
        $timeout = intval($timeout_ms);
        if ($timeout > 0) {
            $this->option[CURLOPT_CONNECTTIMEOUT_MS] = $timeout;
        } else {
            unset($this->option[CURLOPT_CONNECTTIMEOUT_MS]);
        }
        return $this;
    }

    /**
     * 设置请求响应超时时间
     * @param $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $timeout = intval($timeout);
        if ($timeout > 0) {
            $this->option[CURLOPT_TIMEOUT] = $timeout;
        } else {
            unset($this->option[CURLOPT_TIMEOUT]);
        }
        return $this;
    }

    /**
     * 设置post数据与解析模式
     * @param $postdata
     * @param int $postmode
     * @return $this
     */
    public function setPostdata($postdata, $postmode = 0)
    {
        $this->postdata = $postdata;
        $postmode and $this->postmode = intval($postmode);
        $this->analysis_post = false;
        //$this->files = [];
        $this->post = null;
        return $this;
    }

    /**
     * 设置post模式
     * @param int $postmode
     * @return $this
     */
    public function setPostmode($postmode)
    {
        $this->postmode = intval($postmode);
        $this->analysis_post = false;
        //$this->files = [];
        $this->post = null;
        return $this;
    }


    /**
     * 设置代理
     * @param $proxy
     * @return $this
     */
    public function setProxy($proxy = false)
    {
        unset($this->option[CURLOPT_PROXY]);
        unset($this->option[CURLOPT_HTTPPROXYTUNNEL]);
        unset($this->option[CURLOPT_PROXYPORT]);
        unset($this->option[CURLOPT_PROXYAUTH]);
        unset($this->option[CURLOPT_PROXYTYPE]);
        unset($this->option[CURLOPT_PROXYUSERPWD]);

        if (!$proxy || (!is_string($proxy) && empty($proxy['host']))) {
            return $this;
        }

        if (is_string($proxy)) {
            $proxy = ['host'=>$proxy, 'tunnel'=>true];
        }

        $this->option[CURLOPT_PROXY] = $proxy['host'];
        $this->option[CURLOPT_HTTPPROXYTUNNEL] = !empty($proxy['tunnel']);

        isset($proxy['port']) and $this->option[CURLOPT_PROXYPORT] = intval($proxy['port']);
        isset($proxy['auth']) and $this->option[CURLOPT_PROXYAUTH] = intval($proxy['auth']);
        isset($proxy['type']) and $this->option[CURLOPT_PROXYTYPE] = intval($proxy['type']);
        isset($proxy['userpwd']) and $this->option[CURLOPT_PROXYUSERPWD] = strval($proxy['userpwd']);

        return $this;
    }

    public function setHeader(array $headers){
        $this->headers = $headers;
    }

    public function addHeader($key, $val){
        $this->headers[$key] = $val;
    }

    //以下是获取函数

    /**
     * 获取请求url
     * @return string
     */
    public function getUrl()
    {
        $this->analysis_url or $this->analysisUrl();
        return $this->url;
    }

    /**
     * 判断是否post提交
     * @return bool
     */
    public function isPost(){
        return !is_null($this->postdata);
    }

    /**
     * 是否包含文件提交
     * @return bool
     */
    public function hasFile(){
        $this->analysis_post or $this->analysisPost();
        return !empty($this->files);
    }

    /**
     * 获取post原始请求数据
     * @return null
     */
    public function getPostdata()
    {
        return $this->postdata;
    }


    //以下是内部函数

    /**
     * 解析url
     */
    protected function analysisUrl(){
        $this->analysis_url = true;
        $info = parse_url($this->set_url);
        if (empty($info['scheme']) || empty($info['host'])) {
            $this->url = '';
            return;
        }

        $user_str = '';
        if (!empty($info['user'])) {
            if (!empty($info['pass'])) {
                $user_str = ':'.$info['pass'];
            }

            $user_str = $info['user'].$user_str.'@';
        }

        $url = "{$info['scheme']}://{$user_str}{$info['host']}";
        empty($info['port']) or $url .= ':'.$info['port'];

        empty($info['path']) or $url .= $info['path'];

        $query = [];
        empty($info['query']) or parse_str($info['query'], $query);

        $query_str = $this->analysisQuery($query);
        $query_str and $url .= '?'.$query_str;

        empty($info['fragment']) or $url .= '#'.$info['fragment'];
        $this->url = $url;
    }

    /**
     * 分析get请求参数
     * @param array $query
     * @return bool|string
     */
    protected function analysisQuery($query = []){
        is_array($query) or $query = [];
        $query = array_merge($query, $this->query);
        return http_build_query($query);
    }

    /**
     * 分析post请求数据
     */
    protected function analysisPost()
    {
        if ($this->analysis_post) {
            return;
        }
        $this->analysis_post = true;
        $post = [CURLOPT_CUSTOMREQUEST=>'POST'];
        switch ($this->postmode) {
            case self::MODE_POST_HTTP:
                if (is_array($this->postdata)) {
                    $post[CURLOPT_POSTFIELDS] = http_build_query($this->postdata);
                } elseif (is_string($this->postdata)) {
                    $post[CURLOPT_POSTFIELDS] = $this->postdata;
                }


                //在数据是数组时设置
                /* if (class_exists('CURLFile') && defined('CURLOPT_SAFE_UPLOAD')) {
                     $reqopt[CURLOPT_SAFE_UPLOAD]=true;
                 }*/
                //CURLOPT_POSTFIELDS为数组是设置CURLOPT_POST为true，主要用于文件提交
                //$reqopt[CURLOPT_POST] = true;
                break;
            case self::MODE_POST_HESSIAN:
                /*if (!empty($this->postdata['func'])) {
                    $params = !empty($this->postdata['params'])?$this->postdata['params']:[];
                    $stream = "H\x02\x00";
                    $stream .= 'C';
                    $stream .= $this->writeString($this->postdata['func']);
                    $stream .= $this->writeInt(count($params));
                    foreach ($params as $param) {
                        $stream .= $this->writeValue($param);
                    }
                }*/
                //CURLOPT_HTTPHEADER => array("Content-Type: application/binary")
                break;
            default:
                $post = [];
        }

        $this->post = $post;

        if (!is_null($this->post)) {
            $reqopt[CURLOPT_POSTFIELDS] = $this->post;
        }
    }


    /**
     * 响应Headers解析
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders)
    {
        $headers = array();

        $lines = explode("\r\n", trim($rawHeaders));
        $headers['HTTP'] = array_shift($lines);

        foreach ($lines as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }

        return $headers;
    }


    //以下是开放的功能性函数


    public function clean($all = false){
        $this->set_url = '';
        $this->url = '';
        $this->query = [];
        $this->postdata = null;
        $this->postmode = 0;
        $all and $this->option = [];
        return $this;
    }

    /**
     * 批量设置
     * @param array $option
     * @return $this
     */
    public function multiSet(array $option = [])
    {
        foreach ($option as $key => $val) {
            $setfunc = 'set'.ucfirst(strtolower($key));
            if (method_exists($this, $setfunc)) {
                $this->$setfunc($val);
            }
        }
        return $this;
    }


    /**
     * 使用curl请求，并返回结果
     * @return array
     */
    public function request()
    {
        $header = '';
        $reqopt = [
            CURLOPT_URL=>$this->getUrl(),
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HEADER=>false,
            CURLOPT_HEADERFUNCTION => function () use(&$header) {
                $str = func_get_arg(1);

                $header .= $str;
                return strlen($str);
            }
        ];


        $reqopt += $this->ssl;
        $this->analysis_post or $this->analysisPost();
        $reqopt += $this->post;
        $reqopt += $this->option;


        $ch = curl_init();
        curl_setopt_array($ch, $reqopt);
        $ret = curl_exec($ch);

        $result = [
            'header'=>false,
            'request'=>$this->post,
            'response'=>false,
            'errno'=>curl_errno($ch),
            'error'=>curl_error($ch),
            'info'=>curl_getinfo($ch),
        ];
        curl_close($ch);

        $result['header'] = $this->splitHeaders($header);
        $result['response'] = $ret;
        return $result;
    }

    public function getFailResult()
    {
        $this->analysis_post or $this->analysisPost();
        $result = [
            'header'=>false,
            'request'=>$this->post,
            'response'=>false,
            'errno'=>0,
            'error'=>'',
            'info'=>[
                'url'=>$this->getUrl(),
                'content_type' => NULL,
                'http_code' => 0,
                'header_size' => 0,
                'request_size' => 0,
                'filetime' => 0,
                'ssl_verify_result' => 0,
                'redirect_count' => 0,
                'total_time' => 0,
                'namelookup_time' => 0.0,
                'connect_time' => 0.0,
                'pretransfer_time' => 0.0,
                'size_upload' => 0.0,
                'size_download' => 0.0,
                'speed_download' => 0.0,
                'speed_upload' => 0.0,
                'download_content_length' => 0,
                'upload_content_length' => 0,
                'starttransfer_time' => 0,
                'redirect_time' => 0,
                'redirect_url' => '',
                'primary_ip' => '',
                'certinfo' =>[],
                'primary_port' => 0,
                'local_ip' => '',
                'local_port' => 0,
            ],
        ];

        return $result;
    }


    //以下是静态函数

    /**
     * 快速初始化
     * @param array $option
     * @return self
     */
    public static function init(array $option = []){
        $req = new self();
        return $req->multiSet($option);
    }

    /**
     * 设置默认参数
     * @param array $option
     * @param bool $cover
     */
    public static function initOption(array $option = [], $cover = false)
    {
        $cover and self::$doption = [];
        $req = self::init($option);
        self::$doption = $req->option;
    }

    /**
     * 快速请求
     * @param array $option
     * @return array
     */
    public static function quickRequest(array $option){
        return self::init($option)->request();
    }
}