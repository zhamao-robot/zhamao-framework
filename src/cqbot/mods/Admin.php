<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/19
 * Time: 14:55
 */

/**
 * Class Admin
 * 框架管理模块，里面已经附带了一些查看状态、重载和停止的功能
 */
class Admin extends ModBase
{
    public function __construct(CQBot $main, $data) {
        parent::__construct($main, $data);
        $this->split_execute = true;
    }

    public static function initValues() {
        Cache::set("msg_speed", []);//消息速度列表（存的是时间戳）
        Cache::set("admin_active", "");
        Cache::set("admin", settings()["admin"]);
    }

    public static function onTick($tick) {
        if ($tick % 900 == 0) CQUtil::saveAllFiles();//900秒储存一次数据
        if (settings()["save_user_data"]) {
            if ($tick % 21600 == 0) {     //21600秒刷新一次好友列表
                GroupManager::updateGroupList();
                FriendManager::updateFriendList();
            }
        }
    }

    public static function onRequest($req) {
        switch ($req["request_type"]) {
            case "friend":
                //好友添加请求处理
                $comment = $req["comment"];//验证信息
                $flag = $req["flag"];//添加好友用的flag，用于处理好友
                $user_id = $req["user_id"];

                //如果不需要将好友添加信息发到群里，请注释下面这行
                CQAPI::debug("有用户请求添加好友啦！\n用户QQ：" . $user_id . "\n验证信息：" . $comment . "\n添加flag：" . $flag, "");

                //如果不需要要自动同意，请注释下面这几行
                $params = ["flag" => $flag, "approve" => true];
                CQAPI::set_friend_add_request($req["self_id"], $params, function ($response) use ($user_id) {
                    CQAPI::debug("已自动同意 " . $user_id, "", $response["self_id"]);
                    CQAPI::send_private_msg($user_id, ["message" => "你好，第一次见面请多关照！"]);
                });
                break;
        }
    }

    public function execute($it) {
        if (!$this->main->isAdmin($this->getUserId())) return false;
        switch ($it[0]) {
            case "reload":  //管理员重载代码
                $this->reply("正在重新启动...");
                CQUtil::reload();
                return true;
            case "stop":    //管理员停止server
                $this->reply("正在停止服务器...");
                CQUtil::stop();
                return true;
        }
        return false;
    }
}