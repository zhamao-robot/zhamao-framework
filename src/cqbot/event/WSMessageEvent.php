<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:04
 */

class WSMessageEvent extends Event
{
    public function __construct(swoole_websocket_server $server, swoole_websocket_frame $frame) {
        $in_count = Buffer::$in_count->get();
        Buffer::$in_count->add(1);
        $req = json_decode($frame->data, true);
        if (Buffer::$data["info_level"] == 2) {
            Console::put("************EVENT RECEIVED***********");
            Console::put("msg_id = " . $in_count);
            Console::put("worker_id = " . $server->worker_id);
        }
        if (Buffer::$data["info_level"] >= 1) {
            $type = $req["post_type"] == "message" ? ($req["message_type"] == "group" ? "GROUP_MSG:" . $req["group_id"] : ($req["message_type"] == "private" ? "PRIVATE_MSG" : "DISCUSS_MSG:" . $req["discuss_id"])) : strtoupper($req["post_type"]);
            Console::put(Console::setColor(date("H:i:s"), "green") . Console::setColor(" [$in_count]" . $type, "lightlightblue") . Console::setColor(" " . $req["user_id"], "yellow") . Console::setColor(" > ", "gray") . ($req["post_type"] == "message" ? $req["message"] : Console::setColor(CQUtil::executeType($req), "gold")));
        }
        //传入业务逻辑：CQBot
        try {
            $c = new CQBot($this->getFramework());
            $c->execute($req);
            $c->endtime = microtime(true);
            $value = $c->endtime - $c->starttime;
            Console::debug("Using time: ".$value);
            if(Buffer::get("time_send") === true)
                CQUtil::sendDebugMsg("Using time: ".$value);
        } catch (Exception $e) {
            CQUtil::errorlog("处理消息时异常，消息处理中断\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            CQUtil::sendDebugMsg("引起异常的消息：\n" . var_export($req, true));
        }
    }
}