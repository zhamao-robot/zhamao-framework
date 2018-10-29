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
        $fd = $frame->fd;
        $req = json_decode($frame->data, true);
        $connect = CQUtil::getConnection($fd);
        if ($connect === null) {
            Console::info("收到一个未知链接发来的消息。" . $fd);
            return;
        }
        switch ($connect->getType()) {
            case 0:
                //处理酷Q HTTP事件接口消息(Event)
                switch ($req["post_type"]) {
                    case "message":
                        new MessageEvent($req);
                        break 2;
                    case "notice":
                        new NoticeEvent($req);
                        break 2;
                    case "request":
                        new RequestEvent($req);
                        break 2;
                    case "meta_event":
                        new MetaEvent($req);
                        break 2;
                    default:
                        new UnknownEvent($req);
                        break 2;
                }
            case 1:
                Console::debug("收到来自API[" . $fd . "]连接的回复：" . json_encode($req, 128 | 256));
                if (isset($req["echo"])) CQAPIHandler::execute($req["echo"], $req);
                break;
            default:
                Console::info("收到未知链接的消息, 来自: " . $fd);
                break;
        }
    }
}