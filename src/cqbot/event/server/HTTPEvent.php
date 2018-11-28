<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/16
 * Time: 下午2:05
 */

class HTTPEvent extends ServerEvent
{
    public $content;
    public $isValid = false;

    /**
     * HTTPEvent constructor.
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function __construct(swoole_http_request $request, swoole_http_response $response) {
        parent::__construct(Cache::$server);
        $response->end("Hello world!");
        //此为HTTP请求的回复，更多设置回复头、传送文件、POST、GET请求解析等内容请查阅文档https://wiki.swoole.com
    }

    /**
     * 此函数为一个示例的检测有效的函数，为此预留
     * 作用是判断传入的请求数据是合法的
     * @param $param
     * @return bool
     */
    public function isValidParam($param) {
        if ($param === null) return false;
        if (!isset($param["event"])) return false;
        if (!isset($param["timestamp"])) return false;
        if (!isset($param[$param["event"]])) return false;
        if(($param["timestamp"] > (time() + 10)) || ($param["timestamp"] < (time() - 10))) return false;
        return true;
    }
}