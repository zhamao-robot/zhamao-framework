<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:16
 */

class Framework
{
    public $host;
    public $port;

    const VERSION = "1.0.0";

    /** @var \swoole_websocket_server $event */
    public $event;

    public static $obj = null;

    /** @var Scheduler */
    public $scheduler = null;

    public function __construct($config) {
        $this->host = $config["swoole_host"];
        $this->port = $config["swoole_port"];
        //Buffer::set("access_token", $config["access_token"] ?? "");
        //Buffer::set("info_level", $config["info_level"]);
        //Buffer::set("admin_group", $config["admin_group"]);

        $this->selfCheck();

        Console::info("CQBot Framework starting on " . $this->host . ":" . $this->port);
        $this->event = new swoole_websocket_server($this->host, $this->port);

        $this->event->set([
            "log_file" => $config["swoole_log_file"],
            "worker_num" => $config["swoole_worker_num"],
            "dispatch_mode" => $config["swoole_dispatch_mode"]
        ]);

        //swoole服务器启动时运行的函数
        $this->event->on('WorkerStart', [$this, 'onWorkerStart']);

        //swoole服务端收到WebSocket信息时运行的函数
        $this->event->on('message', function ($server, $frame) { new WSMessageEvent($server, $frame); });

        //收到ws连接和断开连接回调的函数
        $this->event->on('open', function (\swoole_websocket_server $server, \swoole_http_request $request) { new WSOpenEvent($server, $request); });
        $this->event->on('close', function (\swoole_server $server, int $fd) { new WSCloseEvent($server, $fd); });

        //设置接收HTTP接口接收的内容，兼容微信公众号和其他服务用
        $this->event->on("request", function (\swoole_http_request $request, \swoole_http_response $response) { new HTTPEvent($request, $response); });

        //设置原子计数器
        Cache::$in_count = new \swoole_atomic(0);
        Cache::$out_count = new \swoole_atomic(0);
        Cache::$reload_time = new \swoole_atomic(0);
        Cache::$api_id = new \swoole_atomic(0);
    }

    public function start() { $this->event->start(); }

    public static function getInstance() { return self::$obj; }

    /* Callback function down here */

    /**
     * This is async function in EventLoop
     * When it reload, it will run this function again.
     * @param \swoole_server $server
     * @param $worker_id
     */
    public function onWorkerStart(\swoole_server $server, $worker_id) {
        self::$obj = $this;
        new WorkerStartEvent($server, $worker_id);
    }

    /**
     * 开启时候的自检模块
     * 检测项目在下面列举
     */
    public function selfCheck() {
        if (!extension_loaded("swoole")) die("无法找到swoole扩展，请先安装.\n");
        if (!function_exists("mb_substr")) die("无法找到mbstring扩展，请先安装.\n");
        if (substr(PHP_VERSION, 0, 1) != "7") die("PHP >=7 required.\n");
        if (!function_exists("curl_exec")) die("无法找到curl扩展，请先安装.\n");
        //if (!class_exists("ZipArchive")) die("无法找到zip扩展，请先安装.（如果不需要zip功能可以删除此条自检）\n");
        if (!is_file(settings()["swoole_log_file"])) file_put_contents(settings()["swoole_log_file"], "");
        return true;
    }
}