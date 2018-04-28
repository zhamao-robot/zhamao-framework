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
    private $host = "127.0.0.1";
    private $api_port = 10000;
    private $event_port = 20000;

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

    public function init($option = null){
        self::$super_user = ($option !== null ? $option : "");
        self::$obj = $this;
        Console::info("CQBot Framework starting...");
        $this->event = new \swoole_websocket_server($this->host, $this->event_port);
        Buffer::$log_file = CONFIG_DIR . "log/swoole.log";
        Console::info("Current log file: " . Buffer::$log_file);
        $worker_num = 1;
        Console::info("Current worker count: " . $worker_num);
        $dispatch_mode = 2;
        Console::info("Current dispatch mode: " . $dispatch_mode);
        $this->checkFiles();
        $this->event->set([
            "log_file" => Buffer::$log_file,
            "worker_num" => 1,
            "dispatch_mode" => 2
        ]);
        $this->event->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->event->on('message', [$this, 'onEventMessage']);
        //$this->event->on('open', [$this, 'onConnect']);
        $this->event->on('open', function ($server, $request){
            Console::put("EVENT connection established", "lightblue");
        });
        $this->event->on("request", [$this, "onRequest"]);
        $this->event->on('close', function ($serv, $fd){
            Console::info(Console::setColor("EVENT connection closed","red"));
            //put your connection close method here.
        });
        Buffer::$in_count = new \swoole_atomic(1);
        Buffer::$out_count = new \swoole_atomic(1);
        Buffer::$api_id = new \swoole_atomic(1);
        return $this;
    }

    public function checkFiles(){
        @mkdir(CONFIG_DIR."log/", 0777, true);
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
        Buffer::set("info_level", $this->info_level);//设置info等级
        Console::info("Starting worker: " . $worker_id);
        require_once(WORKING_DIR . "src/cqbot/loader.php");
        CQUtil::loadAllFiles();
        foreach (get_included_files() as $file)
            Console::debug("Loaded " . $file);

        //计时器（ms）
        $server->tick(1000, [$this, "processTick"]);

        //API连接部分
        $this->api = new \swoole_http_client($this->host, $this->api_port);
        $this->api->set(['websocket_mask' => true]);
        $this->api->on('message', [$this, "onApiMessage"]);
        $this->api->on("close", function ($cli){
            Console::info(Console::setColor("API connection closed", "red"));
        });
        $this->api->upgrade('/api/', [$this, "onUpgrade"]);

        Console::debug("master_pid = " . $server->master_pid);
        Console::debug("worker_id = " . $worker_id);
        Console::put("\n==========STARTUP DONE==========\n");
    }

    public function onUpgrade($cli){
        Console::info("Upgraded API websocket");
        Buffer::$api = $this->api;
        Buffer::$event = $this->event;
        if ($data = file(CONFIG_DIR . "log/last_error.log")) {
            $last_time = file_get_contents(CONFIG_DIR . "log/error_flag");
            if (time() - $last_time < 2) {
                CQUtil::sendDebugMsg("检测到重复引起异常，停止服务器", 0);
                file_put_contents(CONFIG_DIR."log/last_error.log", "");
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

    /**
     * 回调函数：当HTTP插件发来json包后激活此函数
     * @param swoole_websocket_server $server
     * @param $frame
     */
    public function onEventMessage($server, $frame){
        $in_count = Buffer::$in_count->get();
        Buffer::$in_count->add(1);
        $req = json_decode($frame->data, true);
        if (Buffer::$data["info_level"] == 2) {
            Console::put("************EVENT RECEIVED***********");
            Console::put("msg_id = " . $in_count);
            Console::put("worker_id = " . $server->worker_id);
        }
        if (Buffer::$data["info_level"] >= 1) {
            $type = $req["post_type"] == "message" ? ($req["message_type"] == "group" ? "GROUP_MSG:" . $req["group_id"] : ($req["message_type"] == "private" ? "PRIVATE_MSG" : "DISCUSS_MSG:" . $req["discuss_id"])) : strtoupper($req["post_type"]);
            Console::put(Console::setColor(date("H:i:s"), "green") . Console::setColor(" [$in_count]" . $type, "lightlightblue") . Console::setColor(" " . $req["user_id"], "yellow") . Console::setColor(" > ", "gray") . ($req["post_type"] == "message" ? $req["message"] : Console::setColor($this->executeType($req), "gold")));
        }
        //传入业务逻辑：CQBot
        try {
            $c = new CQBot($this);
            $c->execute($req);
            $c->endtime = microtime(true);
            $value = $c->endtime - $c->starttime;
            Console::debug("Using time: ".$value);
            if(Buffer::get("time_send") === true)
                CQUtil::sendDebugMsg("Using time: ".$value);
        } catch (Exception $e) {
            CQUtil::errorlog("处理消息时异常，消息处理中断\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            CQUtil::sendDebugMsg("引起异常的消息：\n" . var_export($req, true));
        }
    }

    /******************* 微信HTTP 响应 ******************
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */

    public function onRequest($request, $response){
        $response->end("Hello world");
    }

    /******************* API 响应 ******************
     * @param swoole_http_client $client
     * @param $frame
     */
    public function onApiMessage($client, $frame){

    }

    /***************** 计时器 ******************
     * @param $id
     */
    public function processTick($id){
        $this->tick_time = time();
        new TickTask($this, $id);
    }

    /**
     * 此函数用于解析其他非消息类型事件，显示在log里
     * @param $req
     * @return string
     */
    public function executeType($req){
        switch($req["post_type"]){
            case "message":
                return "消息";
            case "event":
                switch($req["event"]){
                    case "group_upload":
                        return "群[".$req["group_id"]."] 文件上传：".$req["file"]["name"]."（".intval($req["file"]["size"] / 1024)."kb）";
                    case "group_admin":
                        switch($req["sub_type"]){
                            case "set":
                                return "群[".$req["group_id"]."] 设置管理员：".$req["user_id"];
                            case "unset":
                                return "群[".$req["group_id"]."] 取消管理员：".$req["user_id"];
                            default:
                                return "unknown_group_admin_type";
                        }
                    case "group_decrease":
                        switch($req["sub_type"]){
                            case "leave":
                                return "群[".$req["group_id"]."] 成员主动退群：".$req["user_id"];
                            case "kick":
                                return "群[".$req["group_id"]."] 管理员[".$req["operator_id"]."]踢出了：".$req["user_id"];
                            case "kick_me":
                                return "群[".$req["group_id"]."] 本账号被踢出";
                            default:
                                return "unknown_group_decrease_type";
                        }
                    case "group_increase":
                        return "群[".$req["group_id"]."] ".$req["operator_id"]." 同意 ".$req["user_id"]." 加入了群";
                    default:
                        return "unknown_event";
                }
            case "request":
                switch($req["request_type"]){
                    case "friend":
                        return "加好友请求：".$req["user_id"]."，验证信息：".$req["message"];
                    case "group":
                        switch($req["sub_type"]){
                            case "add":
                                return "加群[".$req["group_id"]."] 请求：".$req["user_id"]."，请求信息：".$req["message"];
                            case "invite":
                                return "用户".$req["user_id"]."邀请机器人进入群：".$req["group_id"];
                            default:
                                return "unknown_group_type";
                        }
                    default:
                        return "unknown_request_type";
                }
            default:
                return "unknown_post_type";
        }
    }
}