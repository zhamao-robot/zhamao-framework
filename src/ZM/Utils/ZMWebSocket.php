<?php


namespace ZM\Utils;


use Framework\Console;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;

/**
 * Class ZMWebSocket
 * @package ZM\Utils
 * @since 1.5
 */
class ZMWebSocket
{
    private $parse;
    private $client;

    public $is_available = false;

    private $close_func;
    private $message_func;

    public function __construct($url, $set = ['websocket_mask' => true], $header = []) {
        $this->parse = parse_url($url);
        if (!isset($this->parse["host"])) {
            Console::warning("ZMRequest: url must contains scheme such as \"ws(s)://\"");
            return;
        }
        if (!isset($this->parse["path"])) $this->parse["path"] = "/";
        $port = $this->parse["port"] ?? (($this->parse["scheme"] ?? "ws") == "wss" ? 443 : 80);
        $this->client = new Client($this->parse["host"], $port, (($this->parse["scheme"] ?? "ws") == "wss" ? true : false));
        $this->client->set($set);
        if ($header != []) $this->client->setHeaders($header);
        $this->is_available = true;
    }

    /**
     * @return bool
     */
    public function upgrade() {
        if (!$this->is_available) return false;
        $r = $this->client->upgrade($this->parse["path"] . (isset($this->parse["query"]) ? ("?" . $this->parse["query"]) : ""));
        if ($r) {
            go(function () {
                while (true) {
                    $result = $this->client->recv(60);
                    if ($result === false) {
                        if ($this->client->connected === false) {
                            go(function () {
                                call_user_func($this->close_func, $this->client);
                            });
                            break;
                        }
                    } elseif ($result instanceof Frame) {
                        go(function () use ($result) {
                            $this->is_available = false;
                            call_user_func($this->message_func, $result, $this->client);
                        });
                    }
                }
            });
            return true;
        }
        return false;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function onMessage(callable $callable) {
        $this->message_func = $callable;
        return $this;
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function onClose(callable $callable) {
        $this->close_func = $callable;
        return $this;
    }
}

if (!debug_backtrace()) {
    go(function () {
        require_once __DIR__ . "/../../Framework/Console.php";
        $cli = new ZMWebSocket("ws://127.0.0.1:20001/");
        if (!$cli->is_available) die("Error!\n");
        $cli->onMessage(function (Frame $frame) {
            var_dump($frame);
        });
        $cli->onClose(function () {
            echo "Connection closed.\n";
        });
        if ($cli->upgrade()) {
            echo "成功连接！\n";
        } else {
            echo "连接失败！\n";
        }
    });
}
