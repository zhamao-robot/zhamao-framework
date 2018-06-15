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
        $req = json_decode($frame->data, true);
        if (isset($req["echo"])) if (APIHandler::execute($req["echo"], $req)) return;
        if (isset($req["echo"]["type"]) && $req["echo"]["type"] === "handshake") {
            $fd_id = $frame->fd;
            $connect = CQUtil::getConnection($fd_id);
            $connect->setQQ($req["user_id"]);
            $connect->setType(1);
            $connect->findSub();
            if ($data = file(CONFIG_DIR . "log/last_error.log")) {
                $last_time = file_get_contents(CONFIG_DIR . "log/error_flag");
                if (time() - $last_time < 2) {
                    CQUtil::sendDebugMsg("检测到重复引起异常，停止服务器", $req["user_id"], 0);
                    file_put_contents(CONFIG_DIR . "log/last_error.log", "");
                    $this->getFramework()->event->shutdown();
                    return;
                }
                CQUtil::sendDebugMsg("检测到异常", $req["user_id"], 0);
                $msg = "";
                foreach ($data as $e) {
                    $msg = $msg . $e . "\n";
                }
                CQUtil::sendDebugMsg($msg, $req["user_id"], 0);
                CQUtil::sendDebugMsg("[CQBot] 成功开启！", $req["user_id"], 0);
                file_put_contents(CONFIG_DIR . "error_flag", time());
                file_put_contents(CONFIG_DIR . "last_error.log", "");
            } else {
                CQUtil::sendDebugMsg("[CQBot] 成功开启！", $req["user_id"], 0);
            }
            CQUtil::sendAPI($frame->fd, "_get_friend_list", ["get_friend_list"]);
            CQUtil::sendAPI($frame->fd, "get_group_list", ["get_group_list"]);
            CQUtil::sendAPI($frame->fd, "get_version_info", ["get_version_info"]);
            return;
        }
        $connect = CQUtil::getConnection($frame->fd);
        switch ($connect->getType()) {
            case 0:
                $connect->setQQ($req["self_id"]);
                $connect->findSub();
                $in_count = Buffer::$in_count->get();
                Buffer::$in_count->add(1);
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
                    $c = new CQBot($this->getFramework(), $req);
                    $c->endtime = microtime(true);
                    $value = $c->endtime - $c->starttime;
                    Console::debug("Using time: " . $value);
                    if (Buffer::get("time_send") === true)
                        CQUtil::sendDebugMsg("Using time: " . $value, $req["self_id"]);
                } catch (Exception $e) {
                    CQUtil::errorlog("处理消息时异常，消息处理中断\n" . $e->getMessage() . "\n" . $e->getTraceAsString(), $req["self_id"]);
                    CQUtil::sendDebugMsg("引起异常的消息：\n" . var_export($req, true), $req['self_id']);
                }
        }

    }
}