<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/19
 * Time: 14:55
 */

class Admin extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data) {
        parent::__construct($main, $data);
    }

    public static function initValues() {
        Buffer::set("msg_speed", []);//消息速度列表（存的是时间戳）
        Buffer::set("admin_active", "");
    }

    public static function onTick($tick) {
        if ($tick % 900 == 0) CQUtil::saveAllFiles();//900秒储存一次数据
        if ($tick % 21600 == 0) {//21600秒刷新一次好友列表
            GroupManager::updateGroupList();
            FriendManager::updateFriendList();
        }
    }

    public function execute($it) {
        if (!$this->main->isAdmin($this->getUserId())) return false;
        switch ($it[0]) {
            case "reload"://管理员重载代码
                $this->reply("正在重新启动...");
                if (file_get_contents("/home/ubuntu/CrazyBotFramework/src/Framework.php") != Buffer::get("res_code"))
                    $this->reply("检测到改变了Framework文件的内容！如需完全重载，请重启完整进程！");
                CQUtil::reload();
                return true;
            case "stop"://管理员停止server
                $this->reply("正在停止服务器...");
                CQUtil::stop();
                return true;
            case "op"://添加管理员
                $user = $it[1];
                Buffer::append("admin", $user);
                $this->reply("added operator $user");
                return true;
            case "deop"://删除管理员
                $user = $it[1];
                if (Buffer::in_array("admin", $user)) Buffer::unsetByValue("admin", $user);
                $this->reply("removed operator $user");
                return true;
        }
        return false;
    }
}