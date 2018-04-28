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
        Buffer::set("su", DP::getJsonData("su.json"));//超级管理员用户列表
        if (count(Buffer::get("su")) < 1 && Framework::$super_user !== "") {
            Console::info("Added super user");
            Buffer::set("su", [Framework::$super_user]);
        }
        Buffer::set("mods", self::getMods());//加载模块列表
        Buffer::set("user", []);//清空用户列表
        Buffer::set("time_send", false);//发送Timing数据到管理群
        Buffer::set("cmd_prefix", DP::getJsonData("config.json")["cmd_prefix"] ?? "");//设置指令的前缀符号
        Buffer::set("res_code", file_get_contents(WORKING_DIR."src/cqbot/Framework.php"));
    }

    public static function saveAllFiles() {
        Console::info("Saving files...");
        DP::setJsonData("su.json", Buffer::get("su"));//保存超级管理员的QQ列表

        //保存cmd_prefix（指令前缀）
        $config = DP::getJsonData("config.json");
        $config["cmd_prefix"] = Buffer::get("cmd_prefix");
        DP::setJsonData("config.json", $config);

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
        if (self::checkAPIConnection() === -1) {
            file_put_contents(DP::getDataFolder() . "last_error.log", $msg, FILE_APPEND);
        } else {
            if ($send_debug_message)
                self::sendDebugMsg($msg, 0);
        }
    }

    /**
     * 发送调试信息到管理群（需先设置管理群号）
     * @param $msg
     * @param int $need_head
     * @return null
     */
    static function sendDebugMsg($msg, $need_head = 1) {
        if (Framework::$admin_group == "") return null;
        if ($need_head)
            $data = CQMsg("[DEBUG] " . date("H:i:s") . ": " . $msg, "group", Framework::$admin_group);
        else
            $data = CQMsg($msg, "group", Framework::$admin_group);
        return self::APIPush($data);
    }

    /**
     * 检查API端口连接情况
     * @return int
     */
    static function checkAPIConnection() {
        if (Buffer::$api === null) return -1;//在framework链接API之前
        if (Buffer::$api->isConnected() === false) {
            //链接被断开
            Buffer::$api->upgrade('/api/', function ($cli) {
                self::sendDebugMsg("API重新链接成功");
                self::APIPushDelayMsg();
            });
            return 0;
        }
        return 1;
    }

    /**
     * 推送API，给API端口
     * @param $data
     * @return bool
     */
    static function APIPush($data) {
        if ($data == null || $data == "") {
            Console::error("EMPTY DATA PUSH");
            return false;
        }
        if (self::checkAPIConnection() === -1) {
            //忽略掉framework链接API之前的消息
            self::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", 0);
            return false;
        }
        if (self::checkAPIConnection() === 0) {
            self::APIPushAfterConnected($data);
            return true;
        }
        if (Buffer::$api->push($data) === false) {
            $data = self::unicodeDecode($data);
            self::errorlog("API推送失败，未发送的消息: \n" . $data, "API ERROR", 0);
            self::sendErrorEmail("API推送失败", "未成功推送的消息：<br>$data<br>请检查酷q是否开启及网络链接情况<br>在此期间，机器人会中断所有消息处理<br>请及时处理");
            return false;
        }
        return true;
    }

    /**
     * 延迟推送在API连接断开后收到的消息函数
     */
    static function APIPushDelayMsg() {
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
    }

    /**
     * 推迟推送API，用于酷Q重启后的重新连接API
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
     * @param string $name
     * @param int $send_debug_message
     * @return bool|string
     */
    static function sendEmail($address, $title, $content, $name = "CQ开发团队", $send_debug_message = 1) {
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
            self::errorLog("发送邮件错误！错误信息：" . $info = $mail->ErrorInfo, "ERROR", $send_debug_message);
            unset($mail);
            return $info;
        }
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
     * @param string $name
     */
    static function sendErrorEmail($title, $content, $name = "机器人错误提示") {
        self::sendEmail(["here your receive email address"], $title, $content, $name, 0);
    }

    /**
     * 获取所有已经加载到内存的用户。
     * read_all为true时，会加载所有User.dat到内存中，false时仅会读取已经加载到内存的用户
     * @param bool $real_all
     * @return array[User]
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
     * @return bool
     */
    static function sendGroupMsg($groupId, $msg) {
        $reply = ["action" => "send_group_msg", "params" => ["group_id" => $groupId, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $reply = json_encode($reply);
        if (self::APIPush($reply)) {
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
     * @return bool
     */
    static function sendPrivateMsg($userId, $msg) {
        $reply = ["action" => "send_private_msg", "params" => ["user_id" => $userId, "message" => $msg]];
        $reply["echo"] = $reply;
        $reply["echo"]["time"] = time();
        $reply = json_encode($reply);
        if (self::APIPush($reply)) {
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
     * @param $data
     * @param $echo
     */
    static function sendAPI($data, $echo) {
        if (!is_array($data)) {
            $api = [];
            $api["action"] = $data;
        } else {
            $api = $data;
        }
        $api["echo"] = $echo;
        self::APIPush(json_encode($api));
    }

    /**
     * 删除一个和模块相关联的指令
     * @param $name
     * @return bool
     */
    static function removeCommand($name) {
        $list = Buffer::get("commands");
        if (!isset($list[$name])) return false;
        unset($list[$name]);
        Buffer::set("commands", $list);
        DP::setJsonData("commands.json", $list);
        return true;
    }

    /**
     * 添加一个指令给非callTask方式激活的模块。
     * 注意：如果给callTask方式激活的模块添加指令，则在使用对应功能时会回复多次同样的内容
     * @param $name
     * @param $class
     * @return bool
     */
    static function addCommand($name, $class) {
        if (!is_file(WORKING_DIR . 'src/cqbot/mods/' . $class . '.php')) {
            return false;
        }
        $list = Buffer::get("commands");
        $list[$name] = $class;
        DP::setJsonData("commands.json", $list);
        Buffer::set("commands", $list);
        return true;
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
    static function reload(){
        Console::info("Reloading server");
        self::saveAllFiles();
        Buffer::$event->reload();
    }

    /**
     * 停止运行框架，需要用shell再次开启才能启动
     */
    static function stop(){
        Console::info("Stopping server...");
        self::saveAllFiles();
        Buffer::$api->close();
        Buffer::$event->shutdown();
    }
}