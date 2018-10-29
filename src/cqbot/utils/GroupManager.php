<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/9/17
 * Time: 2:00 PM
 */

class GroupManager
{
    /**
     * 更新群组列表
     * @param null $qq
     */
    public static function updateGroupList($qq = null) {
        Buffer::set("group_list", []);
        foreach (CQUtil::getConnections("api") as $k => $v) {
            $fd = $v->fd;
            $robot_id = $v->getQQ();
            Console::put("正在获取机器人 " . $robot_id . " 的群组列表...");
            CQUtil::sendAPI($fd, "get_group_list", ["get_group_list", "step1"]);
        }
    }
}