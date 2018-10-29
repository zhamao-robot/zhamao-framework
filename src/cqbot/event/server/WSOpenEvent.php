<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:10
 */

class WSOpenEvent extends Event
{
    public function __construct(swoole_websocket_server $server, swoole_http_request $request) {
        $fd = $request->fd;
        $get = $request->get;
        $header = $request->header;
        //echo json_encode($header, 128|256);
        $connect_type = $get["type"] ?? (isset($header["x-client-role"]) ? strtolower($header["x-client-role"]) : "");
        $access_token = $get["token"] ?? (isset($header["authorization"]) ? explode(" ", $header["authorization"])[1] : "");
        //Console::info("链接类型：".$connect_type."\n链接token：".$access_token);
        if ($connect_type == "") {
            Console::info("未指定连接类型，关闭连接.");
            $server->close($fd);
            return;
        }
        if ($access_token == "") {
            Console::info("未指定连接token，关闭连接.");
            $server->close($fd);
            return;
        }
        //if (isset($request->header["authorization"])) {
        //$tokens = explode(" ", $request->header["authorization"]);
        //$tokens = trim($tokens[1]);
        if ($access_token !== Buffer::get("access_token")) {
            Console::info("监测到WS连接，但是token不对，无法匹配。");
            $server->close($fd);
            return;
        }
        switch ($connect_type) {
            case "event":
            case "api":
                $self_id = $get["qq"] ?? ($header["x-self-id"] ?? "");
                Console::info("收到 " . $connect_type . " 连接，来自机器人：" . $self_id . "，fd：" . $fd);
                CQUtil::getConnection($fd, $connect_type, $self_id);
                $robots = [];
                foreach (Buffer::get("robots") as $v) {
                    $robots[] = $v["qq"];
                }
                if (!in_array($self_id, $robots)) {
                    Buffer::append("robots", ["qq" => $self_id, "addr" => $request->server["remote_addr"]]);
                }
                break;
        }
    }
}