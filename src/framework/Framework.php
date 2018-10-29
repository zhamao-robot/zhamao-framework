<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:16
 */

class Framework
{
    public static $super_user;
    public $host = "127.0.0.1";
    public $event_port = 20000;

    /** @var \swoole_websocket_server $event */
    public $event;

    public static $obj = null;

    public $run_time;
    public $info_level = 1;

    /** @var \swoole_http_client $api */
    public $api;
    private $log_file;
    public $tick_time;

    /** @var Scheduler */
    public $scheduler = null;

    public function __construct($config) {
        $this->host = $config["host"];
        $this->event_port = $config["port"];
        Buffer::set("access_token", $config["access_token"] ?? "");
        Buffer::set("info_level", $config["info_level"]);
        Buffer::set("admin_group", $config["admin_group"]);

        $this->selfCheck();

        Console::info("CQBot Framework starting...");
        $this->event = new swoole_websocket_server($this->host, $this->event_port);

        Buffer::$log_file = CRASH_DIR . "swoole.log";

        //设置swoole基本参数
        $worker_num = 1;//进程数调整，默认为1，如果调整worker为多个，则需要自行修改Buffer类的储存方式到redis、或其他数据库等数据结构
        $dispatch_mode = 2;//解析模式，详见wiki.swoole.com
        $this->event->set([
            "log_file" => Buffer::$log_file,
            "worker_num" => $worker_num,
            "dispatch_mode" => $dispatch_mode
        ]);

        //swoole服务器启动时运行的函数
        $this->event->on('WorkerStart', [$this, 'onWorkerStart']);

        //swoole服务端收到WebSocket信息时运行的函数
        $this->event->on('message', [$this, 'onEventMessage']);

        //收到ws连接和断开连接回调的函数
        $this->event->on('open', function (\swoole_websocket_server $server, \swoole_http_request $request) { new WSOpenEvent($server, $request); });
        $this->event->on('close', function (\swoole_server $server, int $fd) { new WSCloseEvent($server, $fd); });

        //设置接收HTTP接口接收的内容，兼容微信公众号和其他服务用
        $this->event->on("request", [$this, "onRequest"]);

        //设置原子计数器
        Buffer::$in_count = new \swoole_atomic(1);
        Buffer::$out_count = new \swoole_atomic(1);
        Buffer::$reload_time = new \swoole_atomic(0);
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
        $this->run_time = time();
        Buffer::set("info_level", $this->info_level);//设置info等级
        Buffer::$event = $server;
        require_once(WORKING_DIR . "src/cqbot/loader.php");
        new WorkerStartEvent($server, $worker_id);
    }

    /**
     * 回调函数：当HTTP插件发来json包后激活此函数
     * @param swoole_websocket_server $server
     * @param $frame
     */
    public function onEventMessage($server, $frame) { new WSMessageEvent($server, $frame); }

    /**
     * 回调函数：当IP:event端口收到相关HTTP请求时候调用
     * 你可在此编写HTTP请求回复的内容（比如做一个web界面？）
     * 也可以在这里处理微信公众号的请求（可能需要端口转发）
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function onRequest($request, $response) { new HTTPEvent($request, $response); }

    /**
     * 回调函数：异步计时器，一秒执行一次。请勿在此使用阻塞IO方法
     * @param $id
     */
    public function processTick($id) { $this->scheduler->tick($id, ($this->tick_time = time())); }

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
        if (!is_file(CRASH_DIR . "swoole.log")) file_put_contents(CRASH_DIR . "swoole.log", "");
        return true;
    }
}