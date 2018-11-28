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
     */
    public static function updateGroupList() {
        Cache::set("group_list", []);
        foreach (ConnectionManager::getAll("robot") as $k => $v) {
            if ($v->getSubType() != "event") {
                $robot_id = $v->getQQ();
                Console::put("正在获取机器人 " . $robot_id . " 的群组列表...");
                $v->sendAPI("get_group_list", [], function ($response) use ($robot_id) {
                    $list = Cache::get("group_list");
                    foreach ($response["data"] as $k => $v) {
                        if (!isset($list[$v["group_id"]])) {
                            $list[$v["group_id"]] = [
                                "group_id" => $v["group_id"],
                                "group_name" => $v["group_name"],
                                "fetch_members" => false,
                                "joiner" => [$robot_id]
                            ];
                        } elseif (!in_array($robot_id, $list[$v["group_id"]]["joiner"])) {
                            $list[$v["group_id"]]["joiner"][] = $robot_id;
                        }
                    }
                    Cache::set("group_list", $list);
                });
            }
        }
    }
}