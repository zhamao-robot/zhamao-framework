<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午4:10
 */

class WSOpenEvent extends ServerEvent
{
    public function __construct(swoole_websocket_server $server, swoole_http_request $request) {
        parent::__construct($server);
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
        //if (isset($request->header["authorization"])) {
        //$tokens = explode(" ", $request->header["authorization"]);
        //$tokens = trim($tokens[1]);
        if ($access_token !== Cache::get("access_token") && Cache::get("access_token") != "") {
            Console::info("监测到WS连接，但是token不对，无法匹配。");
            $server->close($fd);
            return;
        }
        switch ($connect_type) {
            case "event":
            case "api":
            case "universal":
                $self_id = $get["qq"] ?? ($header["x-self-id"] ?? "");
                Console::info("收到 " . $connect_type . " 连接，来自机器人：" . $self_id . "，fd：" . $fd);
                $conn = new RobotWSConnection($server, $fd, $self_id, $request, $connect_type);
                if($conn->create_success) ConnectionManager::set($fd, $conn);
                else {
                    Console::error("初始化WS连接失败！fd：".$fd."，QQ：".$self_id);
                    $server->close($fd);
                    return;
                }
                break;
            case "custom":
                $conn = new CustomWSConnection($server, $fd, $request);
                if($conn->create_success) ConnectionManager::set($fd, $conn);
                break;
            default:
                Console::info("Unknown WS Connection connected. I will close it.");
                $server->close($fd);
        }
    }
}