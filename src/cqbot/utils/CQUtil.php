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
    public static function initEmptyCaches() {
        $ls = [
            "user",         // 储存用户对象的数组
            "sent_api",     // 储存每条API请求的会调函数Closure等原始内容
            "msg_speed",    // 储存记录消息速度的数组
            "bug_msg_list"  // 储存当前状态下所有未发出去的消息列表
        ];
        foreach ($ls as $v) {
            Cache::set($v, []);
        }
    }

    public static function loadAllFiles() {
        Cache::set("info_level", settings()["info_level"]);
        Console::debug("loading configs...");
        Cache::set("mods", self::getMods());//加载的模块列表
        Cache::set("group_list", DP::getJsonData("group_list.json"));//获取群组列表
        Cache::set("admin_group", settings()["admin_group"]);

        //加载全局屏蔽的机器人列表
        Cache::set("bots", DP::getJsonData("bots.json"));

        //调用各个模块单独的Buffer数据
        foreach (self::getMods() as $v) {
            if (in_array("initValues", get_class_methods($v))) {
                $v::initValues();
            }
        }
    }

    public static function saveAllFiles() {
        Console::info("Saving files...   ", null, "");

        DP::setJsonDataAsync("bots.json", Cache::get("bots"));
        DP::setJsonDataAsync("group_list.json", Cache::get("group_list"));

        //保存用户数据
        if (settings()["save_user_data"]) {
            foreach (self::getAllUsers() as $k => $v) {
                $serial = serialize($v);
                file_put_contents(DP::getUserFolder() . $k . ".dat", $serial);
            }
        }

        Console::put("Saved files.");
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
        if ($send_debug_message) CQAPI::debug($msg);
    }

    static function findRobot() {
        foreach (ConnectionManager::getAll("robot") as $v) {
            return $v->getQQ();
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
                    if (!Cache::array_key_exists("user", $vs[0])) {
                        Cache::appendKey("user", $vs[0], $class);
                    }
                }
            }
        }
        return Cache::get("user");
    }

    /**
     * 获取用户实例
     * @param $id
     * @param bool $enable_init
     * @return User
     */
    static function getUser($id, $enable_init = true) {
        $d = Cache::get("user");
        if (!isset($d[$id])) {
            $r = self::initUser($id, $enable_init);
            if (!$r) return null;
            $d = Cache::get("user");
        }
        /** @var User $class */
        $class = $d[$id];
        return $class;
    }

    /**
     * 初始化用户实例。如果没有此用户的实例数据，会创建
     * @param $id
     * @param bool $enable_init
     * @return bool
     */
    static function initUser($id, $enable_init = true) {
        if (file_exists(DP::getUserFolder() . $id . ".dat")) $class = unserialize(file_get_contents(DP::getUserFolder() . $id . ".dat"));
        else {
            if ($enable_init) {
                Console::info("无法找到用户 " . $id . " 的数据，正在创建...");
                $class = new User($id);
            } else return false;
        }
        Cache::appendKey("user", $id, $class);
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
        /** @var ModBase[] $ls */
        for ($i = 0; $i < count($ls) - 1; $i++) {
            for ($j = 0; $j < count($ls) - $i - 1; $j++) {
                $s = defined($ls[$j] . "::mod_level") ? $ls[$j]::mod_level : 10;
                $s1 = defined($ls[$j + 1] . "::mod_level") ? $ls[$j + 1]::mod_level : 10;
                //Console::info("Comparing mod " . $ls[$j] . " with " . $ls[$j + 1] . ", level are " . $s . ", " . $s1);
                if ($s < $s1) {
                    $t = $ls[$j + 1];
                    $ls[$j + 1] = $ls[$j];
                    $ls[$j] = $t;
                }
            }
        }
        for ($i = count($ls) - 1; $i >= 0; $i--) {
            $s = defined($ls[$i] . "::mod_level") ? $ls[$i]::mod_level : 10;
            if ($s === 0) unset($ls[$i]);
        }
        return $ls;
    }

    /**
     * 重启框架，此服务重启为全自动的
     */
    static function reload() {
        Console::info("Reloading server");
        self::saveAllFiles();
        Cache::$server->reload();
    }

    /**
     * 停止运行框架，需要用shell再次开启才能启动
     */
    static function stop() {
        Console::info("Stopping server...");
        self::saveAllFiles();
        Cache::$server->shutdown();
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

    static function isRobot($user_id) {
        $robots = [];
        foreach (ConnectionManager::getAll("robot") as $v) {
            if (!in_array($v->getQQ(), $robots))
                $robots[] = $v->getQQ();
        }
        foreach (Cache::get("bots") as $v) {
            $robots[] = $v;
        }
        return in_array($user_id, $robots);
    }

    static function getRobotAlias($qq) {
        return RobotWSConnection::ALIAS_LIST[$qq] ?? "机器人";
    }

    /**
     * 刷新消息速率(每分钟)
     * 当 $insert 为 true 时，表明运行此函数时收到了一条消息
     * @param bool $insert
     */
    static function updateMsg($insert = true) {
        $ls = Cache::get("msg_speed");
        if ($insert === true)
            $ls [] = time();
        foreach ($ls as $k => $v) {
            if ((time() - $v) > 60) {
                array_splice($ls, $k, 1);
            }
        }
        Cache::set("msg_speed", $ls);
    }
}