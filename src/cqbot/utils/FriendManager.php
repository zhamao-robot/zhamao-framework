<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/9/17
 * Time: 2:02 PM
 */

class FriendManager
{
    /**
     * 更新所有机器人的好友列表
     */
    public static function updateFriendList() {
        $list = CQUtil::getAllUsers(true);
        foreach ($list as $k => $v) {
            $v->setFriend([]);
        }
        foreach (ConnectionManager::getAll("robot") as $k => $v) {
            if ($v->getSubType() != "event") {
                $robot_id = $v->getQQ();
                Console::put("正在获取机器人 " . $robot_id . " 的好友列表...");
                $v->sendAPI("_get_friend_list", ["flat" => "true"], function ($response) use ($robot_id) {
                    foreach ($response["data"]["friends"] as $k => $v) {
                        $user = CQUtil::getUser($v["user_id"]);
                        $ls = $user->getFriend();
                        if (!in_array($robot_id, $ls)) {
                            $ls[] = $robot_id;
                        }
                        $user->setFriend($ls);
                        $user->setNickname($v["nickname"]);
                    }
                    foreach (CQUtil::getAllUsers() as $k => $v) {
                        $serial = serialize($v);
                        file_put_contents(DataProvider::getUserFolder() . $k . ".dat", $serial);
                    }
                    Cache::set("user", []);
                });
            }
        }
    }
}