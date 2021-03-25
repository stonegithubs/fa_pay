<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2018/9/11
 * Time: 上午9:31
 */
namespace tool;
/**
 * 数据格式转换
 * Class Format
 * @package tool
 */
class Format
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
     * 浏览器友好的变量输出
     * @param mixed         $var 变量
     * @param boolean       $echo 是否输出 默认为true 如果为false 则返回输出字符串
     * @param string        $label 标签 默认为空
     * @param integer       $flags htmlspecialchars flags
     * @return void|string
     */
    public static function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, $flags);
        }
        $output = '<pre>' . $label . $output . '</pre>';
        if ($echo) {
            echo($output);
            return;
        } else {
            return $output;
        }
    }


}