<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/14
 * Time: 11:59 PM
 */

class RobotWSConnection extends WSConnection
{
    const ALIAS_LIST = [
        "10001" => "小马哥",
        "838714432" => "鲸鱼的test机器人"
    ];

    private $qq;
    private $alias;//别名
    private $sub_type;

    public function __construct(swoole_websocket_server $server, $fd, $qq, swoole_http_request $request, $sub_type) {
        parent::__construct($server, $fd, $request->server["remote_addr"]);
        $this->qq = $qq;
        $this->alias = self::ALIAS_LIST[$qq] ?? "机器人" . $qq;
        $this->sub_type = $sub_type;
        foreach (ConnectionManager::getAll("robot") as $k => $v) {
            if ($v->getQQ() == $this->getQQ() && $k != $this->fd && $v->getSubType() == $this->getSubType()) {
                $this->getServer()->close($k);
                ConnectionManager::remove($k);
            }
        }
        if ($sub_type != "event") {
            $obj = $this;
            $r = $this->sendAPI("get_version_info", [], function ($response) use ($obj) {
                Cache::set("version_info", $response["data"]);
            });
            if ($r)
                $this->create_success = $this->sendAPI("send_group_msg", ["message" => "[CQBot] 机器人 " . $this->getAlias() . " 已连接，链接fd：" . $this->fd, "group_id" => Cache::get("admin_group")]);
        } else $this->create_success = true;
    }

    /**
     * 返回连接的QQ
     * @return mixed
     */
    public function getQQ() {
        return $this->qq;
    }

    /**
     * @return mixed|string
     */
    public function getAlias() {
        return $this->alias;
    }

    public function sendAPI($api, $params = [], callable $callback = null) {
        $data["action"] = $api;
        if ($params != []) $data["params"] = $params;

        if ($this->sub_type == "event") {
            $conns = ConnectionManager::getAll("robot");
            foreach ($conns as $k => $v) {
                if ($v->getSubType() == "api" && $v->getQQ() == $this->getQQ()) {
                    $api_id = Cache::$api_id->get();
                    $data["echo"] = $api_id;
                    Cache::$api_id->add(1);
                    Cache::appendKey("sent_api", $api_id, [
                        "data" => $data,
                        "time" => microtime(true),
                        "func" => $callback,
                        "self_id" => $this->getQQ()
                    ]);
                    if ($v->push(json_encode($data))) {
                        if (in_array($data["action"], CQAPI::getLoggedAPIs())) {
                            Console::msg($data);
                            Cache::$out_count->add(1);
                        }
                        return true;
                    } else {
                        $response = [
                            "status" => "failed",
                            "retcode" => 998,
                            "data" => null,
                            "self_id" => $this->getQQ()
                        ];
                        $s = Cache::get("sent_api")[$data["echo"]];
                        StatusParser::parse($response, $data);
                        if ($s["func"] !== null)
                            call_user_func($s["func"], $response, $data);
                        Cache::unset("sent_api", $data["echo"]);
                        return false;
                    }
                }
            }
            return false;
        }
        $api_id = Cache::$api_id->get();
        $data["echo"] = $api_id;
        Cache::$api_id->add(1);
        Cache::appendKey("sent_api", $api_id, [
            "data" => $data,
            "time" => microtime(true),
            "func" => $callback,
            "self_id" => $this->getQQ()
        ]);
        if ($this->push(json_encode($data))) {
            if (in_array($data["action"], CQAPI::getLoggedAPIs())) {
                Console::msg($data);
                Cache::$out_count->add(1);
            }
            return true;
        } else {
            $response = [
                "status" => "failed",
                "retcode" => 999,
                "data" => null,
                "self_id" => $this->getQQ()
            ];
            $s = Cache::get("sent_api")[$data["echo"]];
            StatusParser::parse($response, $data);
            if ($s["func"] !== null)
                call_user_func($s["func"], $response, $data);
            Cache::unset("sent_api", $data["echo"]);
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getSubType() {
        return $this->sub_type;
    }
}