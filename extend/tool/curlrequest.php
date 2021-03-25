<?php
class CurlRequest {
    /**
     * 发送HTTP请求方法
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    public function http($url, $params, $method = 'POST'){
        $params_json = json_encode($params);
        $opts = array(
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT=> 10,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_HTTPHEADER] = array(
                    "Content-Type: application/x-www-form-urlencoded; charset=utf-8"
                );
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                $opts[CURLOPT_HTTPHEADER] = array(
                    "Content-Type: application/json; charset=utf-8",
                    "Content-Length: " . strlen($params_json)
                );
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params_json;
                break;
            default:
                throw new Exception('Unsupported request mode.');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error) $data = 'Request error：' . $error;
        return  $data;
    }
}
