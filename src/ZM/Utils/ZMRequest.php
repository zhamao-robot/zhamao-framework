<?php


namespace ZM\Utils;


use Framework\Console;
use Swlib\Saber;
use Swoole\Coroutine\Http\Client;

class ZMRequest
{
    /**
     * 使用Swoole协程客户端发起HTTP GET请求
     * @param $url
     * @param array $headers
     * @param array $set
     * @param bool $return_body
     * @return bool|string|Client
     * @version 1.1
     * 返回请求后的body
     * 如果请求失败或返回状态不是200，则返回 false
     */
    public static function get($url, $headers = [], $set = [], $return_body = true) {
        $parse = parse_url($url);
        if (!isset($parse["host"])) {
            Console::warning("ZMRequest: url must contains scheme such as \"http(s)\"");
            return false;
        }
        if(!isset($parse["path"])) $parse["path"] = "/";
        $port = $parse["port"] ?? (($parse["scheme"] ?? "http") == "https" ? 443 : 80);
        $cli = new Client($parse["host"], $port, (($parse["scheme"] ?? "http") == "https" ? true : false));
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
        if (!isset($parse["host"])) {
            Console::warning("ZMRequest: url must contains scheme such as \"http(s)://\"");
            return false;
        }
        if(!isset($parse["path"])) $parse["path"] = "/";
        $port = $parse["port"] ?? (($parse["scheme"] ?? "http") == "https" ? 443 : 80);
        $cli = new Client($parse["host"], $port, (($parse["scheme"] ?? "http") == "https" ? true : false));
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
     * @param $url
     * @param array $set
     * @param array $header
     * @return ZMWebSocket
     * @since 1.5
     */
    public static function websocket($url, $set = ['websocket_mask' => true], $header = []) {
        return new ZMWebSocket($url, $set, $header);
    }

    /**
     * @param $option
     * @return Saber
     */
    public static function session($option) {
        return Saber::session($option);
    }

    public static function request($url, $attribute = [], $return_body = true) {
        $parse = parse_url($url);
        if (!isset($parse["host"])) {
            Console::warning("ZMRequest: url must contains scheme such as \"http(s)://\"");
            return false;
        }
        if(!isset($parse["path"])) $parse["path"] = "/";
        $port = $parse["port"] ?? (($parse["scheme"] ?? "http") == "https" ? 443 : 80);
        $cli = new Client($parse["host"], $port, (($parse["scheme"] ?? "http") == "https" ? true : false));
        $cli->set($attribute["set"] ?? ["timeout" => 15.0]);
        $cli->setMethod($attribute["method"] ?? "GET");
        $cli->setHeaders($attribute["headers"] ?? []);
        if(isset($attribute["data"])) $cli->setData($attribute["data"]);
        if(isset($attribute["file"])) {
            foreach($attribute["file"] as $k => $v) {
                $cli->addFile($v["path"], $v["name"], $v["mime_type"] ?? null, $v["filename"] ?? null, $v["offset"] ?? 0, $v["length"] ?? 0);
            }
        }
        $cli->execute($parse["path"] . (isset($parse["query"]) ? "?" . $parse["query"] : ""));
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
}
