<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/15
 * Time: 10:35 AM
 */

class MessageEvent extends Event
{
    public function __construct($req) {
        if (CQUtil::isRobot($req["user_id"])) return;

        $in_count = Buffer::$in_count->get();
        Buffer::$in_count->add(1);
        if (Buffer::$data["info_level"] >= 1) {
            $num = CQUtil::getRobotNum($req["self_id"]);
            $type = $req["post_type"] == "message" ? ($req["message_type"] == "group" ? "GROUP_MSG" . $num . ":" . $req["group_id"] : ($req["message_type"] == "private" ? "PRIVATE_MSG" . $num : "DISCUSS_MSG" . $num . ":" . $req["discuss_id"])) : strtoupper($req["post_type"]);
            Console::put(Console::setColor(date("H:i:s"), "green") . Console::setColor(" [$in_count]" . $type, "lightlightblue") . Console::setColor(" " . $req["user_id"], "yellow") . Console::setColor(" > ", "gray") . $req["message"]);
        }

        CQUtil::updateMsg();//更新消息速度

        //传入消息处理的逻辑
        $c = new CQBot($this->getFramework(), 0, $req);
        $c->execute();
        $value = $c->endtime - $c->starttime;
        Console::debug("Using time: " . $value);

    }
}