<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/3/29
 * Time: 11:16
 */

namespace cqbot;

use cqbot\utils\Buffer;
use cqbot\utils\CQUtil;

class Framework
{
    private $host = "127.0.0.1";
    private $api_port = 10000;
    private $event_port = 20000;

    /** @var \swoole_websocket_server $event */
    public $event;

    public static $obj = null;

    private $run_time;
    public static $admin_group;
    public $info_level = 1;

    /** @var \swoole_http_client $api */
    public $api;
    private $log_file;

    public function __construct(){ }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host = ""){
        $this->host = $host;
        return $this;
    }

    public function setApiPort($port = 10000){
        $this->api_port = $port;
        return $this;
    }

    public function setEventPort($port = 20000){
        $this->event_port = $port;
        return $this;
    }

    public function setAdminGroup($group){
        self::$admin_group = $group;
        return $this;
    }

    public function setInfoLevel($level){
        $this->info_level = $level;
        return $this;
    }

    public function eventServerStart(){
        $this->event->start();
    }

    public static function getInstance(){
        return self::$obj;
    }

    public function init(){
        self::$obj = $this;
        Console::put("CQBot Framework starting...");
        $this->event = new \swoole_websocket_server($this->host, $this->event_port);
        Buffer::$log_file = CONFIG_DIR . "log/swoole.log";
        Console::put("Current log file: " . Buffer::$log_file);
        $worker_num = 1;
        Console::put("Current worker count: " . $worker_num);
        $dispatch_mode = 2;
        Console::put("Current dispatch mode: " . $dispatch_mode);
        $this->checkFiles();
        $this->event->set([
            "log_file" => Buffer::$log_file,
            "worker_num" => 1,
            "dispatch_mode" => 2
        ]);
        $this->event->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->event->on('message', [$this, 'onEventMessage']);
        $this->event->on('open', [$this, 'onConnect']);
        $this->event->on('close', function ($serv, $fd){
            //put your connection close method here.
        });
        Buffer::$in_count = new \swoole_atomic(1);
        Buffer::$out_count = new \swoole_atomic(1);
        Buffer::$api_id = new \swoole_atomic(1);
        return $this;
    }

    public function checkFiles(){
        @mkdir(WORKING_DIR."log/", 0777, true);
        if(!is_file(CONFIG_DIR."log/last_error.log"))
            file_put_contents(CONFIG_DIR."log/last_error.log", "");
        if(!is_file(CONFIG_DIR."log/error_flag"))
            file_put_contents(CONFIG_DIR."log/error_flag", time());
    }

    /* Callback function down here */

    /**
     * This is async function in EventLoop
     * When it reload, it will run this function again.
     * @param \swoole_server $server
     * @param $worker_id
     */
    public function onWorkerStart(\swoole_server $server, $worker_id){
        $this->run_time = time();
        Console::info("Starting worker " . $worker_id);
        Console::info("Loading source code...");
        require_once(WORKING_DIR . "src/cqbot/loader.php");
        CQUtil::loadAllFiles();
        Buffer::set("info_level", $this->info_level);//设置info等级
        foreach (get_included_files() as $file)
            Console::debug("Loaded " . $file);
        echo("\n");

        //计时器（ms）
        $server->tick(1000, [$this, "processTick"]);

        $this->api = new \swoole_http_client($this->host, $this->api_port);
        $this->api->set(['websocket_mask' => true]);
        $this->api->on('message', [$this, "onApiMessage"]);
        $this->api->on("close", function ($cli){
            Console::info(Console::setColor("API connection closed", "red"));
        });
        $this->api->upgrade('/api/', [$this, "onUpgrade"]);

        Console::debug("master_pid = " . $server->master_pid);
        Console::debug("worker_id = " . $worker_id);
        Console::put("\n====================\n");
    }

    public function onUpgrade($cli){
        Console::info("Upgraded API websocket");
        Buffer::$api = $this->api;
        Buffer::$event = $this->event;
        if ($data = file(CONFIG_DIR . "last_error.log")) {
            $last_time = file_get_contents(CONFIG_DIR . "error_flag");
            if (time() - $last_time < 2) {
                CQUtil::sendDebugMsg("检测到重复引起异常，停止服务器", 0);
                file_put_contents(CONFIG_DIR."last_error.log", "");
                $this->event->shutdown();
                return;
            }
            CQUtil::sendDebugMsg("检测到异常", 0);
            $msg = "";
            foreach ($data as $e) {
                $msg = $msg . $e . "\n";
            }
            CQUtil::sendDebugMsg($msg, 0);
            CQUtil::sendDebugMsg("[CQBot] 成功开启！", 0);
            file_put_contents(CONFIG_DIR . "error_flag", time());
            file_put_contents(CONFIG_DIR . "last_error.log", "");
        }
        else {
            CQUtil::sendDebugMsg("[CQBot] 成功开启！", 0);
        }
    }
}