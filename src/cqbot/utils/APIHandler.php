<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/26
 * Time: 下午1:42
 */

class APIHandler
{
    static function execute($cmd, $res = null) {
        if (!isset($cmd["type"])) return false;
        switch ($cmd["type"]) {
            case "set_friend_add_request":
                $id = $cmd["params"][0];
                $msg = "Hi～你好！";
                $msg .= "\n第一次见面请多关照！";
                CQUtil::sendPrivateMsg($id, $msg, $cmd["self_id"]);
                return true;
            case "get_friend_list":
                $friend = $res["data"][0]["friends"];
                $list = [];
                foreach ($friend as $k => $v) {
                    $list[$v["user_id"]] = $friend[$k];
                }
                Buffer::set("friend_list", $list);
                Console::put(Console::setColor("已读取" . count(Buffer::get("friend_list")) . "个好友", "blue"));
                return true;
            case "update_group_member_list":
                $group_id = $cmd["params"][0];
                $info_data = $res["data"];
                Console::info(Console::setColor("Updating group $group_id members, it will take several minutes.", "yellow"));
                foreach ($info_data as $k => $v) {
                    $s = new GroupMember($v["user_id"], CQUtil::getGroup($group_id), $v);
                    CQUtil::getGroup($group_id)->setMember($v["user_id"], $s);
                    $s->updateData();
                }
                return true;
            case "update_group_member_info":
                $info_data = $res["data"];
                $group = $cmd["params"][0];
                $user = $cmd["params"][1];
                $g = CQUtil::getGroup($group);
                $member = $g->getMember($user);
                $member->setAttribute($info_data);
                $member->setCard($info_data["card"]);
                $member->setJoinTime($info_data["join_time"]);
                $member->setLastSentTime($info_data["last_sent_time"]);
                $member->setRole($info_data["role"]);
                Console::info("Updated group member information: " . $group . ":" . $user);
                return true;
            case "update_group_info":
                $group = $res["data"];
                $current = $cmd["params"][0];
                $list = [];
                foreach ($group as $k => $v) {
                    $list[$v["group_id"]] = $group[$k];
                }
                if (!isset($list[$current]) && Buffer::array_key_exists("groups", $current)) {
                    Buffer::unset("groups", $current);
                    return true;
                }
                $g = CQUtil::getGroup($current);
                $g->setGroupName($list[$current]["group_name"]);
                return true;
            case "get_group_member_list":
                $group_data = $res["data"];
                $ls = Buffer::get("group_list");
                $group_id = $cmd["params"][0];
                $ls[$group_id]["member"] = $group_data;
                $group = new Group($group_id, $ls[$group_id], $cmd["self_id"]);
                //TODO: 添加获取API时多账号对群的实例化的支持
                Buffer::appendKey("groups", $group_id, $group);
                return true;
            case "get_group_list":
                $group = $res["data"];
                $list = [];
                foreach ($group as $k => $v) {
                    $list[$v["group_id"]] = $group[$k];
                    CQUtil::sendAPI(CQUtil::getApiConnectionByQQ($cmd["self_id"])->fd, ["action" => "get_group_member_list", "params" => ["group_id" => $v["group_id"]]], ["get_group_member_list", $v["group_id"]]);
                }
                Buffer::set("group_list", $list);
                Console::put(Console::setColor("已读取" . count(Buffer::get("group_list")) . "个群", "blue"));
                return true;
            case "get_version_info":
                Buffer::set("version_info", $res["data"]);
                return true;
        }
        return false;
    }
}