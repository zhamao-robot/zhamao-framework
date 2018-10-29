<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:39
 */

use DataProvider as DP;

class CQUtil
{
    public static function loadAllFiles() {
        Console::debug("loading configs...");
        Buffer::set("mods", self::getMods());//加载的模块列表
        Buffer::set("user", []);//清空用户列表
        Buffer::set("time_send", false);//发送Timing数据到管理群
        Buffer::set("res_code", file_get_contents(WORKING_DIR . "src/framework/Framework.php"));
        Buffer::set("group_list", DP::getJsonData("group_list.json"));//获取群组列表


        //加载全局屏蔽的机器人列表
        Buffer::set("bots", DP::getJsonData("bots.json"));

        //调用各个模块单独的Buffer数据
        foreach (self::getMods() as $v) {
            if (in_array("initValues", get_class_methods($v))) {
                $v::initValues();
            }
        }
    }

    public static function saveAllFiles() {
        Console::info("Saving files...");

        DP::setJsonData("bots.json", Buffer::get("bots"));
        DP::setJsonData("group_list.json", Buffer::get("group_list"));


        //保存用户数据
        foreach (self::getAllUsers() as $k => $v) {
            $serial = serialize($v);
            file_put_contents(DP::getUserFolder() . $k . ".dat", $serial);
        }
        Console::info("Saved files");
    }

    /**
     * 生成报错日志
     * @param $log
     * @param string $head
     * @param int $send_debug_message
     */
    public static function errorLog($log, $head = "ERROR", $send_debug_message = 1) {
        Console::error($log, ($head === "ERROR") ? null : "[" . $head . "] ");
        $time = date("Y-m-d H:i:s");
        $msg = "[$head @ $time]: $log\n";
        file_put_contents(DP::getDataFolder() . "log_error.txt", $msg, FILE_APPEND);
        if ($send_debug_message)
            self::sendDebugMsg($msg);
    }

    /**
     * 发送调试信息到管理群（需先设置管理群号）
     * @param $msg
     * @param $self_id
     * @param int $need_head
     * @return null
     */
    static function sendDebugMsg($msg, $self_id = null, $need_head = 1) {
        if ($self_id === null) $self_id = self::findRobot();
        if ($self_id === null) return null;

        if ((Buffer::get("admin_group") ?? "") == "") return null;
        if ($need_head)
            $data = CQMsg("[DEBUG] " . date("H:i:s") . ": " . $msg, "group", Buffer::get("admin_group"));
        else
            $data = CQMsg($msg, "group", Buffer::get("admin_group"));
        $connect = CQUtil::getApiConnectionByQQ($self_id);
        return self::sendAPI($connect->fd, $data, ["send_debug_msg"]);
    }

    static function findRobot() {
        foreach (self::getConnections("api") as $v) {
            return $v->getQQ();
        }
        return null;
    }

    /**
     * 推送API，给API端口
     * @param $fd
     * @param $data
     * @return bool
     */
    static function APIPush($fd, $data) {
        if ($data == null || $data == "") {
            Console::error("EMPTY DATA PUSH");
            return false;
        }
        /*if (self::checkAPIConnection() === -1) {
            //忽略掉framework链接API之前的消息
            self::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", 0);
            return false;
        }
        if (self::checkAPIConnection() === 0) {
            self::APIPushAfterConnected($data);
            return true;
        }*/
        if (Buffer::$event->push($fd, $data) === false) {
            $data = self::unicodeDecode($data);
            self::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", 0);
            return false;
        }
        return true;
    }

    /**
     * 延迟推送在API连接断开后收到的消息函数//待定
     */
    /*static function APIPushDelayMsg() {
        $delay_push_list = Buffer::get("delay_push");
        $cur_time = time();
        foreach ($delay_push_list as $item) {
            $data = $item["data"];
            $time = $item["time"];
            if ($cur_time - $time <= 10) {
                self::APIPush($data);
            }
        }
        Buffer::set("delay_push", []);
    }*/

    /**
     * 推迟推送API，用于酷Q重启后的重新连接API//待定
     * @param $data
     */
    static function APIPushAfterConnected($data) {
        $delay_push_list = Buffer::get("delay_push");
        $delay_push_list[] = ["data" => $data, "time" => time()];
        Buffer::set("delay_push", $delay_push_list);
    }

    /**
     * 解码unicode中文编码
     * @param $str
     * @return null|string|string[]
     */
    static function unicodeDecode($str) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
            return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
        },
            $str);
    }

    /**
     * 模拟发送一个HTML-get请求
     * @param $url
     * @return mixed
     */
    static function getHTML($url) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    /**
     * 获取字符串的反转结果
     * @param $str
     * @param string $encoding
     * @return string
     */
    static public function getRev($str, $encoding = 'utf-8') {
        $result = '';
        $len = mb_strlen($str);
        for ($i = $len - 1; $i >= 0; $i--) {
            $result .= mb_substr($str, $i, 1, $encoding);
        }
        return $result;
    }

    /**
     * 发送邮件功能，基于PHPMailer模块，需先安装phpmailer。默认此工程demo里已包含有phpmailer了。
     * 请根据实际自己的邮箱更新下面的用户名、密码、smtp服务器地址、端口等。
     * 此功能非基于本作者编写的代码，如有问题请在github上找PHPMailer项目进行反馈
     * @param $address
     * @param $title
     * @param $content
     * @param $self_id
     * @param string $name
     * @param int $send_debug_message
     * @return bool|string
     */
    static function sendEmail($address, $title, $content, $self_id, $name = "CQ开发团队", $send_debug_message = 1) {
        $mail = new \PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'here your smtp host';
            $mail->SMTPAuth = true;
            $mail->Username = 'here your mailbox address';
            $mail->Password = 'here your password';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->setFrom('here your mailbox address', $name);
            if (is_array($address)) {
                foreach ($address as $item)
                    $mail->addAddress($item);
            } else {
                $mail->addAddress($address);
            }
            //Content
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->CharSet = "UTF-8";
            $mail->Body = $content;
            $mail->send();
            if (is_array($address))
                $address = implode("，", $address);
            Console::info("向 $address 发送的邮件完成");
            unset($mail);
            return true;
        } catch (\Exception $e) {
            self::errorLog("发送邮件错误！错误信息：" . $info = $mail->ErrorInfo, "ERROR", 0);
            unset($mail);
            return $info;
        }
    }

    /**
     * 返回所有api、event连接
     * @param string $type
     * @return WSConnection[]
     */
    static function getConnections($type = "all") {
        switch ($type) {
            case "all":
                return Buffer::$connect;
            case "event":
                $ls = [];
                foreach (Buffer::$connect as $fd => $connection) {
                    if ($connection->getType() === 0) {
                        $ls[$fd] = $connection;
                    }
                }
                return $ls;
            case "api":
                $ls = [];
                foreach (Buffer::$connect as $fd => $connection) {
                    if ($connection->getType() === 1) {
                        $ls[$fd] = $connection;
                    }
                }
                return $ls;
            default:
                Console::error("寻找连接时链接类型传入错误！");
                return [];
        }
    }

    /**
     * @param $fd
     * @param null $type
     * @param null $qq
     * @return WSConnection
     */
    static function getConnection($fd, $type = null, $qq = null) {
        //var_dump(Buffer::$connect);
        if (!isset(Buffer::$connect[$fd]) && $type !== null && $qq !== null) {
            Console::info("创建连接 " . $fd . " 中...");
            $s = new WSConnection(Buffer::$event, $fd, $type, $qq);
            if ($s->create_success) {
                Buffer::$connect[$fd] = $s;
                Console::debug("创建成功！");
                $s->initConnection();
            } else return null;
        }
        return Buffer::$connect[$fd] ?? null;
    }

    static function getApiConnectionByQQ($qq) {
        foreach (self::getConnections() as $fd => $c) {
            if ($c->getType() === 1 && $c->getQQ() == $qq) {
                return $c;
            }
        }
        return null;
    }

    /**
     * 获取运行时间
     * @param $time
     * @return array
     */
    static function getRunTime($time) {
        $time_len = time() - $time;
        $run_time = [];
        if (intval($time_len / 86400) > 0) {
            $run_time[0] = intval($time_len / 86400);
            $time_len = $time_len % 86400;
        } else {
            $run_time[0] = 0;
        }
        if (intval($time_len / 3600) > 0) {
            $run_time[1] = intval($time_len / 3600);
            $time_len = $time_len % 3600;
        } else {
            $run_time[1] = 0;
        }
        if (intval($time_len / 60) > 0) {
            $run_time[2] = intval($time_len / 60);
            $time_len = $time_len % 60;
        } else {
            $run_time[2] = 0;
        }
        $run_time[3] = $time_len;
        return $run_time;
    }

    /**
     * 获取格式化的运行时间
     * @param $time
     * @return string
     */
    static function getRunTimeFormat($time) {
        $time_len = time() - $time;
        $msg = "";
        if (intval($time_len / 86400) > 0) {
            $msg .= intval($time_len / 86400) . "天";
            $time_len = $time_len % 86400;
        }
        if (intval($time_len / 3600) > 0) {
            $msg .= intval($time_len / 3600) . "小时";
            $time_len = $time_len % 3600;
        }
        if (intval($time_len / 60) > 0) {
            $msg .= intval($time_len / 60) . "分";
            $time_len = $time_len % 60;
        }
        $msg .= $time_len . "秒";
        return $msg;
    }

    /**
     * 检查是否为群组管理员或群主功能，此功能需要先获取群组列表，否则会产生一个warning
     * @param $group
     * @param $user_id
     * @return bool
     */
    static function isGroupAdmin($group, $user_id) {
        $ls = Buffer::get("group_list")[$group]["member"];
        $is_admin = false;
        foreach ($ls as $k => $v) {
            if ($v["user_id"] == $user_id) {
                if ($v["role"] == "admin" || $v["role"] == "owner") {
                    $is_admin = true;
                    break;
                }
            }
        }
        return $is_admin;
    }

    /**
     * 用于发送错误日志邮件的功能，请根据实际情况填写邮箱。
     * 此功能基于sendMail，请看上方sendMail函数的介绍
     * @param $title
     * @param $content
     * @param $self_id
     * @param string $name
     */
    static function sendErrorEmail($title, $content, $self_id, $name = "机器人错误提示") {
        self::sendEmail(["here your receive email address"], $title, $content, $self_id, $name, 0);
    }

    /**
     * 获取所有已经加载到内存的用户。
     * read_all为true时，会加载所有User.dat到内存中，false时仅会读取已经加载到内存的用户
     * @param bool $real_all
     * @return User[]
     */
    static function getAllUsers($real_all = false): array {
        if ($real_all === true) {
            $dir = scandir(DP::getUserFolder());
            unset($dir[0], $dir[1]);
            foreach ($dir as $d => $v) {
                $vs = explode(".", $v);
                if (array_pop($vs) == "dat") {
                    $class = unserialize(file_get_contents(DP::getUserFolder() . $v));
                    if (!Buffer::array_key_exists("user", $vs[0])) {
                        Buffer::appendKey("user", $vs[0], $class);
                    }
                }
            }
        }
        return Buffer::get("user");
    }

    /**
     * 获取用户实例
     * @param $id
     * @return User
     */
    static function getUser($id) {
        $d = Buffer::get("user");
        if (!isset($d[$id])) {
            self::initUser($id);
            $d = Buffer::get("user");
        }
        /** @var User $class */
        $class = $d[$id];
        return $class;
    }

    /**
     * 初始化用户实例。如果没有此用户的实例数据，会创建
     * @param $id
     */
    static function initUser($id) {
        if (file_exists(DP::getUserFolder() . $id . ".dat")) $class = unserialize(file_get_contents(DP::getUserFolder() . $id . ".dat"));
        else {
            Console::info("无法找到用户 " . $id . " 的数据，正在创建...");
            $class = new User($id);
        }
        Buffer::appendKey("user", $id, $class);
    }

    /**
     * 发送群组消息，含控制台推出
     * @param $groupId
     * @param $msg
     * @param string $self_id
     * @return bool
     */
    static function sendGroupMsg($groupId, $msg, $self_id) {
        $reply = ["action" => "send_group_msg", "params" => ["group_id" => $groupId, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $connections = CQUtil::getApiConnectionByQQ($self_id);
        if ($connections === null) {
            Console::error("未找到qq号：" . $self_id . "的API连接");
            return false;
        } else {
            $api_fd = $connections->fd;
        }
        if (self::sendAPI($api_fd, $reply, ["send_group_msg"])) {
            if (Buffer::$data["info_level"] == 1) {
                $out_count = Buffer::$out_count->get();
                Console::put(Console::setColor(date("H:i:s "), "lightpurple") . Console::setColor("[{$out_count}]GROUP", "blue") . Console::setColor(" " . $groupId, "yellow") . Console::setColor(" > ", "gray") . $msg);
                Buffer::$out_count->add(1);
            }
            return true;
        }
        return false;
    }

    /**
     * 发送私聊消息
     * @param $userId
     * @param $msg
     * @param $self_id
     * @return bool
     */
    static function sendPrivateMsg($userId, $msg, $self_id) {
        $reply = ["action" => "send_private_msg", "params" => ["user_id" => $userId, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $connections = CQUtil::getApiConnectionByQQ($self_id);
        if ($connections === null) {
            Console::error("未找到qq号：" . $self_id . "的API连接");
            return false;
        } else {
            $api_fd = $connections->fd;
        }
        if (self::sendAPI($api_fd, $reply, ["send_private_msg"])) {
            if (Buffer::$data["info_level"] == 1) {
                $out_count = Buffer::$out_count->get();
                Console::put(Console::setColor(date("H:i:s "), "lightpurple") . Console::setColor("[{$out_count}]PRIVATE", "blue") . Console::setColor(" " . $userId, "yellow") . Console::setColor(" > ", "gray") . $msg);
                Buffer::$out_count->add(1);
            }
            return true;
        }
        return false;
    }


    static function getFriendName($qq) { return Buffer::get("friend_list")[$qq]["nickname"] ?? "unknown"; }

    static function getGroupName($group) { return Buffer::get("group_list")[$group]["group_name"] ?? "unknown"; }

    /**
     * 发送其他API，HTTP插件支持的其他API都可以发送。
     * echo是返回内容，可以在APIHandler.php里面解析
     * @param $fd
     * @param $data
     * @param $echo
     * @return bool
     */
    static function sendAPI($fd, $data, $echo) {
        if (!is_array($data)) {
            $api = [];
            $api["action"] = $data;
        } else {
            $api = $data;
        }
        $rw = $echo;
        $echo = [
            "self_id" => self::getConnection($fd)->getQQ(),
            "type" => array_shift($rw),
            "params" => $rw
        ];
        $api["echo"] = $echo;
        Console::info("将要发送的API包：" . json_encode($api, 128 | 256));
        return self::APIPush($fd, json_encode($api));
    }

    /**
     * 获取模块列表的通用方法
     * @return array
     */
    static function getMods() {
        $dir = WORKING_DIR . "src/cqbot/mods/";
        $dirs = scandir($dir);
        $ls = [];
        unset($dirs[0], $dirs[1]);
        foreach ($dirs as $v) {
            if ($v != "ModBase.php" && (strstr($v, ".php") !== false)) {
                $name = substr($v, 0, -4);
                $ls[] = $name;
                Console::debug("loading mod: " . $name);
            }
        }
        return $ls;
    }

    /**
     * 判断模块是否存在
     * @param $mod_name
     * @return bool
     */
    static function isModExists($mod_name) {
        $ls = self::getMods();
        return in_array($mod_name, $ls);
    }

    /**
     * 重启框架，此服务重启为全自动的
     */
    static function reload() {
        Console::info("Reloading server");
        self::saveAllFiles();
        Buffer::$event->reload();
    }

    /**
     * 停止运行框架，需要用shell再次开启才能启动
     */
    static function stop() {
        Console::info("Stopping server...");
        self::saveAllFiles();
        Buffer::$api->close();
        Buffer::$event->shutdown();
    }

    /**
     * 此函数用于解析其他非消息类型事件，显示在log里
     * @param $req
     * @return string
     */
    static function executeType($req) {
        switch ($req["post_type"]) {
            case "message":
                return "消息";
            case "event"://兼容3.x
                switch ($req["event"]) {
                    case "group_upload":
                        return "群[" . $req["group_id"] . "] 文件上传：" . $req["file"]["name"] . "（" . intval($req["file"]["size"] / 1024) . "kb）";
                    case "group_admin":
                        switch ($req["sub_type"]) {
                            case "set":
                                return "群[" . $req["group_id"] . "] 设置管理员：" . $req["user_id"];
                            case "unset":
                                return "群[" . $req["group_id"] . "] 取消管理员：" . $req["user_id"];
                            default:
                                return "unknown_group_admin_type";
                        }
                    case "group_decrease":
                        switch ($req["sub_type"]) {
                            case "leave":
                                return "群[" . $req["group_id"] . "] 成员主动退群：" . $req["user_id"];
                            case "kick":
                                return "群[" . $req["group_id"] . "] 管理员[" . $req["operator_id"] . "]踢出了：" . $req["user_id"];
                            case "kick_me":
                                return "群[" . $req["group_id"] . "] 本账号被踢出";
                            default:
                                return "unknown_group_decrease_type";
                        }
                    case "group_increase":
                        return "群[" . $req["group_id"] . "] " . $req["operator_id"] . " 同意 " . $req["user_id"] . " 加入了群";
                    default:
                        return "unknown_event";
                }
            case "notice":
                switch ($req["notice_type"]) {
                    case "group_upload":
                        return "群[" . $req["group_id"] . "] 文件上传：" . $req["file"]["name"] . "（" . intval($req["file"]["size"] / 1024) . "kb）";
                    case "group_admin":
                        switch ($req["sub_type"]) {
                            case "set":
                                return "群[" . $req["group_id"] . "] 设置管理员：" . $req["user_id"];
                            case "unset":
                                return "群[" . $req["group_id"] . "] 取消管理员：" . $req["user_id"];
                            default:
                                return "unknown_group_admin_type";
                        }
                    case "group_decrease":
                        switch ($req["sub_type"]) {
                            case "leave":
                                return "群[" . $req["group_id"] . "] 成员主动退群：" . $req["user_id"];
                            case "kick":
                                return "群[" . $req["group_id"] . "] 管理员[" . $req["operator_id"] . "]踢出了：" . $req["user_id"];
                            case "kick_me":
                                return "群[" . $req["group_id"] . "] 本账号被踢出";
                            default:
                                return "unknown_group_decrease_type";
                        }
                    case "group_increase":
                        return "群[" . $req["group_id"] . "] " . $req["operator_id"] . " 同意 " . $req["user_id"] . " 加入了群";
                    default:
                        return "unknown_event";
                }
            case "request":
                switch ($req["request_type"]) {
                    case "friend":
                        return "加好友请求：" . $req["user_id"] . "，验证信息：" . ($req["message"] ?? $req["comment"]);//兼容3.x
                    case "group":
                        switch ($req["sub_type"]) {
                            case "add":
                                return "加群[" . $req["group_id"] . "] 请求：" . $req["user_id"] . "，请求信息：" . ($req["message"] ?? $req["comment"]);//兼容3.x
                            case "invite":
                                return "用户" . $req["user_id"] . "邀请机器人进入群：" . $req["group_id"];
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

    static function getCQ($msg) {
        if (($start = mb_strpos($msg, '[')) === false) return null;
        if (($end = mb_strpos($msg, ']')) === false) return null;
        $msg = mb_substr($msg, $start + 1, $end - $start - 1);
        if (mb_substr($msg, 0, 3) != "CQ:") return null;
        $msg = mb_substr($msg, 3);
        $msg2 = explode(",", $msg);
        $type = array_shift($msg2);
        $array = [];
        foreach ($msg2 as $k => $v) {
            $ss = explode("=", $v);
            $sk = array_shift($ss);
            $array[$sk] = implode("=", $ss);
        }
        return ["type" => $type, "params" => $array, "start" => $start, "end" => $end];
    }

    static function isRobot($user_id) {
        $robots = [];
        foreach (Buffer::get("robots") as $v) {
            $robots[] = $v["qq"];
        }
        foreach (Buffer::get("bots") as $v) {
            $robots[] = $v;
        }
        return in_array($user_id, $robots);
    }

    static function getRobotNum($qq) {
        $ls = Buffer::get("robots");
        foreach ($ls as $k => $v) {
            if ($v["qq"] == $qq)
                return $k;
        }
        return null;
    }

    /**
     * 刷新消息速率(每分钟)
     * 当 $insert 为 true 时，表明运行此函数时收到了一条消息
     * @param bool $insert
     */
    static function updateMsg($insert = true) {
        $ls = Buffer::get("msg_speed");
        if ($insert === true)
            $ls [] = time();
        foreach ($ls as $k => $v) {
            if ((time() - $v) > 60) {
                array_splice($ls, $k, 1);
            }
        }
        Buffer::set("msg_speed", $ls);
    }
}