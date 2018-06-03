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
    public $api_port = 10000;
    public $event_port = 20000;

    /** @var \swoole_websocket_server $event */
    public $event;

    public static $obj = null;

    public $run_time;
    public static $admin_group;
    public $info_level = 1;

    /** @var \swoole_http_client $api */
    public $api;
    private $log_file;
    public $tick_time;

    /** @var Scheduler */
    public $scheduler = null;

    public function __construct(){ }

    public function setHost($host = ""){ $this->host = $host; }

    public function setApiPort($port = 10000){ $this->api_port = $port; }

    public function setEventPort($port = 20000){ $this->event_port = $port; }

    public function setAdminGroup($group){ self::$admin_group = $group; }

    public function setInfoLevel($level){ $this->info_level = $level; }

    public function eventServerStart(){ $this->event->start(); }

    public static function getInstance(){ return self::$obj; }

    public function init($option = []){
        $this->selfCheck();
        $this->checkFiles();
        self::$super_user = $option;
        Console::info("CQBot Framework starting...");
        $this->event = new \swoole_websocket_server($this->host, $this->event_port);

        Buffer::$log_file = CONFIG_DIR . "log/swoole.log";
        Console::info("Current log file: " . Buffer::$log_file);

        //设置swoole基本参数
        $worker_num = 1;
        $dispatch_mode = 2;
        $this->event->set([
            "log_file" => Buffer::$log_file,
            "worker_num" =>$worker_num,
            "dispatch_mode" => $dispatch_mode
        ]);

        //swoole服务器启动时运行的函数
        $this->event->on('WorkerStart', [$this, 'onWorkerStart']);

        //swoole服务端收到WebSocket信息时运行的函数
        $this->event->on('message', [$this, 'onEventMessage']);

        //收到ws连接和断开连接回调的函数
        $this->event->on('open', [$this, 'onEventOpen']);
        $this->event->on('close', [$this, "onEventClose"]);

        //设置接收HTTP接口接收的内容，兼容微信公众号和其他服务用
        $this->event->on("request", [$this, "onRequest"]);

        //设置原子计数器
        Buffer::$in_count = new \swoole_atomic(1);
        Buffer::$out_count = new \swoole_atomic(1);
        Buffer::$api_id = new \swoole_atomic(1);
    }

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
        require_once(WORKING_DIR . "src/cqbot/loader.php");
        new WorkerStartEvent($server, $worker_id);
    }

    /**
     * 回调函数：API连接升级为WebSocket时候调用，可用于成功和酷Qhttp建立连接的检测依据
     * @param $cli
     */
    public function onUpgrade($cli){ new ApiUpgradeEvent($cli); }

    /**
     * 回调函数：有客户端或HTTP插件反向客户端连接时调用
     * @param swoole_websocket_server $server
     * @param swoole_http_request $request
     */
    public function onEventOpen(\swoole_websocket_server $server, \swoole_http_request $request){ new WSOpenEvent($server, $request); }

    public function onEventClose(\swoole_server $server, int $fd) { new WSCloseEvent($server, $fd); }
    /**
     * 回调函数：当HTTP插件发来json包后激活此函数
     * @param swoole_websocket_server $server
     * @param $frame
     */
    public function onEventMessage($server, $frame){ new WSMessageEvent($server, $frame); }

    /**
     * 回调函数：当IP:event端口收到相关HTTP请求时候调用
     * 你可在此编写HTTP请求回复的内容（比如做一个web界面？）
     * 也可以在这里处理微信公众号的请求（可能需要端口转发）
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function onRequest($request, $response){ new HTTPEvent($request, $response); }

    /**
     * 回调函数：API响应函数，用于发送api请求后返回的状态包的检查，比如rescode = 200
     * @param swoole_http_client $client
     * @param $frame
     */
    public function onApiMessage($client, $frame){ new ApiMessageEvent($client, $frame); }

    /**
     * 回调函数：异步计时器，一秒执行一次。请勿在此使用过多的阻塞方法
     * @param $id
     */
    public function processTick($id){ $this->scheduler->tick($id, ($this->tick_time = time())); }

    /**
     * 开启时候的自检模块
     * 检测项目在下面列举
     */
    public function selfCheck() {
        if (!extension_loaded("swoole")) die("无法找到swoole扩展，请先安装.\n");
        if (!function_exists("mb_substr")) die("无法找到mbstring扩展，请先安装.\n");
        if (substr(PHP_VERSION, 0, 1) != "7") die("PHP >=7 required.\n");
        if (!function_exists("curl_exec")) die("无法找到curl扩展，请先安装.\n");
        if (!class_exists("ZipArchive")) die("无法找到zip扩展，请先安装.（如果不需要zip功能可以删除此条自检）\n");
        return true;
    }

    /**
     * 检查必需的文件是否存在
     */
    public function checkFiles(){
        @mkdir(CONFIG_DIR."log/", 0777, true);
        if(!is_file(CONFIG_DIR."log/last_error.log"))
            file_put_contents(CONFIG_DIR."log/last_error.log", "");
        if(!is_file(CONFIG_DIR."log/error_flag"))
            file_put_contents(CONFIG_DIR."log/error_flag", time());
    }
}