<?php


namespace ZM\Utils;


use Swlib\Saber;
use Swoole\Coroutine\Http\Client;

class ZMRequest
{
    /**
     * 使用Swoole协程客户端发起HTTP GET请求
     * @version 1.1
     * 返回请求后的body
     * 如果请求失败或返回状态不是200，则返回 false
     * @param $url
     * @param array $headers
     * @param array $set
     * @param bool $return_body
     * @return bool|string|Client
     */
    public static function get($url, $headers = [], $set = [], $return_body = true) {
        $parse = parse_url($url);
        $cli = new Client($parse["host"], ($parse["scheme"] == "https" ? 443 : (isset($parse["port"]) ? $parse["port"] : 80)), ($parse["scheme"] == "https" ? true : false));
        $cli->setHeaders($headers);
        $cli->set($set == [] ? ['timeout' => 15.0] : $set);
        $cli->get($parse["path"] . (isset($parse["query"]) ? "?" . $parse["query"] : ""));
        if ($return_body) {
            if ($cli->errCode != 0 || $cli->statusCode != 200) return false;
            $a = $cli->body;
            $cli->close();
            return $a;
        } else {
            $cli->close();
            return $cli;
        }
    }

    /**
     * 使用Swoole协程客户端发起HTTP POST请求
     * 返回请求后的body
     * 如果请求失败或返回状态不是200，则返回 false
     * @param $url
     * @param array $header
     * @param $data
     * @param array $set
     * @param bool $return_body
     * @return bool|string|Client
     */
    public static function post($url, array $header, $data, $set = [], $return_body = true) {
        $parse = parse_url($url);
        $cli = new Client($parse["host"], ($parse["scheme"] == "https" ? 443 : (isset($parse["port"]) ? $parse["port"] : 80)), ($parse["scheme"] == "https" ? true : false));
        $cli->set($set == [] ? ['timeout' => 15.0] : $set);
        $cli->setHeaders($header);
        $cli->post($parse["path"] . (isset($parse["query"]) ? ("?" . $parse["query"]) : ""), $data);
        if ($return_body) {
            if ($cli->errCode != 0 || $cli->statusCode != 200) return false;
            $a = $cli->body;
            $cli->close();
            return $a;
        } else {
            $cli->close();
            return $cli;
        }
    }

    /**
     * @param $option
     * @return Saber
     */
    public static function session($option) {
        return Saber::session($option);
    }
}