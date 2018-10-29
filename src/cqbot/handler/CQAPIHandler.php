<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/26
 * Time: 下午1:42
 */

class CQAPIHandler
{
    static function execute($cmd, $res = null) {
        if (!isset($cmd["type"])) return false;
        if ($res["status"] != "ok" && $res["status"] != "async") {
            $email_contact = [103, 201, -1, -2, -14];
            if (in_array($res["retcode"], $email_contact)) {
                CQUtil::errorLog("API推送失败, retcode = " . $res["retcode"], "API ERROR", 0);
            } else {
                CQUtil::errorLog("API推送失败, retcode = " . $res["retcode"] . "\n说明  = " . ErrorStatus::getMessage($res["retcode"]) . "\n" . json_encode($res["echo"], 128 | 256), "API ERROR");
            }
            echo("\n\n");
        }
        switch ($cmd["type"]) {
            case "get_group_list"://Done
                self::getGroupList($cmd["self_id"], $cmd["params"], $res["data"]);
                break;
            case "get_friend_list"://Done
                self::getFriendList($cmd["self_id"], $cmd["params"], $res["data"]["friends"]);
                break;
            case "set_friend_add_request":
                $id = $cmd["params"][0];
                $msg = "Hi～你好！";
                $msg .= "\n第一次见面请多关照！";
                CQUtil::sendPrivateMsg($id, $msg, $cmd["self_id"]);
                return true;
        }
        return false;
    }

    /**
     * 更新群列表API
     * @param $self_id
     * @param $param
     * @param $data
     */
    static function getGroupList($self_id, $param, $data) {
        switch ($param[0]) {
            case "step1":
                $list = Buffer::get("group_list");
                foreach ($data as $k => $v) {
                    if (!isset($list[$v["group_id"]])) {
                        $list[$v["group_id"]] = [
                            "group_id" => $v["group_id"],
                            "group_name" => $v["group_name"],
                            "fetch_members" => false,
                            "joiner" => [$self_id]
                        ];
                    } elseif (!in_array($self_id, $list[$v["group_id"]]["joiner"])) {
                        $list[$v["group_id"]]["joiner"][] = $self_id;
                    }
                }
                Buffer::set("group_list", $list);
                break;
        }
    }

    /**
     * 更新好友列表API
     * @param $self_id
     * @param $param
     * @param $data
     */
    static function getFriendList($self_id, $param, $data) {
        switch ($param[0]) {
            case "step1":
                foreach ($data as $k => $v) {
                    $user = CQUtil::getUser($v["user_id"]);
                    $ls = $user->getFriend();
                    if (!in_array($self_id, $ls)) {
                        $ls[] = $self_id;
                    }
                    $user->setFriend($ls);
                    $user->setNickname($v["nickname"]);
                }
                foreach (CQUtil::getAllUsers() as $k => $v) {
                    $serial = serialize($v);
                    file_put_contents(DataProvider::getUserFolder() . $k . ".dat", $serial);
                }
                Buffer::set("user", []);
                break;
        }
    }
}