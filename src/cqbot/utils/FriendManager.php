<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/9/17
 * Time: 2:02 PM
 */

class FriendManager
{

    public static function updateFriendList() {
        $list = CQUtil::getAllUsers(true);
        foreach ($list as $k => $v) {
            $v->setFriend([]);
        }
        foreach (CQUtil::getConnections("api") as $k => $v) {
            $fd = $v->fd;
            $robot_id = $v->getQQ();
            Console::put("正在获取机器人 " . $robot_id . " 的好友列表...");
            CQUtil::sendAPI($fd, ["action" => "_get_friend_list", "params" => ["flat" => "true"]], ["get_friend_list", "step1"]);
        }
    }
}