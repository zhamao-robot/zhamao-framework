<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:04
 */

class WSMessageEvent extends ServerEvent
{
    public function __construct(swoole_websocket_server $server, swoole_websocket_frame $frame) {
        parent::__construct($server);
        $fd = $frame->fd;
        $req = json_decode($frame->data, true);
        $conn = ConnectionManager::get($fd);
        if ($conn === null) {
            Console::info("收到一个未知链接发来的消息。" . $fd);
            return;
        }
        switch ($conn->getType()) {
            case "robot":
                //处理酷Q HTTP事件接口消息(Event)
                if (isset($req["post_type"])) {
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
                } else {
                    Console::debug("收到来自API[" . $fd . "]连接的回复：" . json_encode($req, 128 | 256));
                    if (isset($req["echo"]) && Cache::array_key_exists("sent_api", $req["echo"])) {
                        $status = $req["status"];
                        $retcode = $req["retcode"];
                        $data = $req["data"];
                        $origin = Cache::get("sent_api")[$req["echo"]];
                        $self_id = $origin["self_id"];
                        $response = [
                            "status" => $status,
                            "retcode" => $retcode,
                            "data" => $data,
                            "self_id" => $self_id
                        ];
                        StatusParser::parse($response, $origin);
                        if ($origin["func"] !== null)
                            call_user_func($origin["func"], $response, $origin);
                        Cache::removeKey("sent_api", $req["echo"]);
                    }
                }
                break;
            default:
                Console::info("收到未知链接的消息, 来自: " . $fd);
                break;
        }
    }
}