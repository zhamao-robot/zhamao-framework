<?php


namespace ZM\API;


use Co;
use Framework\Console;
use Framework\ZMBuf;
use ZM\Connection\ConnectionManager;
use ZM\Connection\CQConnection;
use ZM\Connection\WSConnection;

/**
 * @method static send_private_msg($self_id, $params, $function = null)
 * @method static send_group_msg($self_id, $params, $function = null)
 * @method static send_discuss_msg($self_id, $params, $function = null)
 * @method static send_msg($self_id, $params, $function = null)
 * @method static delete_msg($self_id, $params, $function = null)
 * @method static send_like($self_id, $params, $function = null)
 * @method static set_group_kick($self_id, $params, $function = null)
 * @method static set_group_ban($self_id, $params, $function = null)
 * @method static set_group_anonymous_ban($self_id, $params, $function = null)
 * @method static set_group_whole_ban($self_id, $params, $function = null)
 * @method static set_group_admin($self_id, $params, $function = null)
 * @method static set_group_anonymous($self_id, $params, $function = null)
 * @method static set_group_card($self_id, $params, $function = null)
 * @method static set_group_leave($self_id, $params, $function = null)
 * @method static set_group_special_title($self_id, $params, $function = null)
 * @method static set_discuss_leave($self_id, $params, $function = null)
 * @method static set_friend_add_request($self_id, $params, $function = null)
 * @method static set_group_add_request($self_id, $params, $function = null)
 * @method static get_login_info($self_id, $params, $function = null)
 * @method static get_stranger_info($self_id, $params, $function = null)
 * @method static get_group_list($self_id, $params, $function = null)
 * @method static get_group_member_info($self_id, $params, $function = null)
 * @method static get_group_member_list($self_id, $params, $function = null)
 * @method static get_cookies($self_id, $params, $function = null)
 * @method static get_csrf_token($self_id, $params, $function = null)
 * @method static get_credentials($self_id, $params, $function = null)
 * @method static get_record($self_id, $params, $function = null)
 * @method static get_status($self_id, $params, $function = null)
 * @method static get_version_info($self_id, $params, $function = null)
 * @method static set_restart($self_id, $params, $function = null)
 * @method static set_restart_plugin($self_id, $params, $function = null)
 * @method static clean_data_dir($self_id, $params, $function = null)
 * @method static clean_plugin_log($self_id, $params, $function = null)
 * @method static _get_friend_list($self_id, $params, $function = null)
 * @method static _get_group_info($self_id, $params, $function = null)
 * @method static _get_vip_info($self_id, $params, $function = null)
 * @method static send_private_msg_async($self_id, $params, $function = null)
 * @method static send_group_msg_async($self_id, $params, $function = null)
 * @method static send_discuss_msg_async($self_id, $params, $function = null)
 * @method static send_msg_async($self_id, $params, $function = null)
 * @method static delete_msg_async($self_id, $params, $function = null)
 * @method static set_group_kick_async($self_id, $params, $function = null)
 * @method static set_group_ban_async($self_id, $params, $function = null)
 * @method static set_group_anonymous_ban_async($self_id, $params, $function = null)
 * @method static set_group_whole_ban_async($self_id, $params, $function = null)
 * @method static set_group_admin_async($self_id, $params, $function = null)
 * @method static set_group_anonymous_async($self_id, $params, $function = null)
 * @method static set_group_card_async($self_id, $params, $function = null)
 * @method static set_group_leave_async($self_id, $params, $function = null)
 * @method static set_group_special_title_async($self_id, $params, $function = null)
 * @method static set_discuss_leave_async($self_id, $params, $function = null)
 * @method static set_friend_add_request_async($self_id, $params, $function = null)
 * @method static set_group_add_request_async($self_id, $params, $function = null)
 */
class CQAPI
{
    public static function quick_reply(WSConnection $conn, $data, $msg, $yield = null) {
        switch ($data["message_type"]) {
            case "group":
                return self::send_group_msg($conn, ["group_id" => $data["group_id"], "message" => $msg], $yield);
            case "private":
                return self::send_private_msg($conn, ["user_id" => $data["user_id"], "message" => $msg], $yield);
            case "discuss":
                return self::send_discuss_msg($conn, ["discuss_id" => $data["discuss_id"], "message" => $msg], $yield);
        }
        return null;
    }

    public static function __callStatic($name, $arg) {
        $all = self::getSupportedAPIs();
        $find = null;
        if (in_array($name, $all)) $find = $name;
        else {
            foreach ($all as $v) {
                if (strtolower($name) == strtolower(str_replace("_", "", $v))) {
                    $find = $v;
                    break;
                }
            }
        }
        if ($find === null) {
            Console::warning("Unknown API " . $name);
            return false;
        }
        $reply = ["action" => $find];
        if (!is_array($arg[1])) {
            Console::warning("Error when parsing params. Please make sure your params is an array.");
            return false;
        }
        if ($arg[1] != []) {
            $reply["params"] = $arg[1];
        }
        if (!($arg[0] instanceof CQConnection)) {
            $robot = ConnectionManager::getByType("qq", ["self_id" => $arg[0]]);
            if ($robot == []) {
                Console::warning("发送错误，机器人连接不存在！");
                return false;
            }
            $arg[0] = $robot[0];
        }
        return self::processAPI($arg[0], $reply, $arg[2] ?? null);
    }

    /**********************   non-API Part   **********************/

    private static function getSupportedAPIs() {
        return [
            "send_private_msg",
            "send_group_msg",
            "send_discuss_msg",
            "send_msg",
            "delete_msg",
            "send_like",
            "set_group_kick",
            "set_group_ban",
            "set_group_anonymous_ban",
            "set_group_whole_ban",
            "set_group_admin",
            "set_group_anonymous",
            "set_group_card",
            "set_group_leave",
            "set_group_special_title",
            "set_discuss_leave",
            "set_friend_add_request",
            "set_group_add_request",
            "get_login_info",
            "get_stranger_info",
            "get_group_list",
            "get_group_member_info",
            "get_group_member_list",
            "get_cookies",
            "get_csrf_token",
            "get_credentials",
            "get_record",
            "get_status",
            "get_version_info",
            "set_restart",
            "set_restart_plugin",
            "clean_data_dir",
            "clean_plugin_log",
            "_get_friend_list",
            "_get_group_info",
            "_get_vip_info",
            //异步API
            "send_private_msg_async",
            "send_group_msg_async",
            "send_discuss_msg_async",
            "send_msg_async",
            "delete_msg_async",
            "set_group_kick_async",
            "set_group_ban_async",
            "set_group_anonymous_ban_async",
            "set_group_whole_ban_async",
            "set_group_admin_async",
            "set_group_anonymous_async",
            "set_group_card_async",
            "set_group_leave_async",
            "set_group_special_title_async",
            "set_discuss_leave_async",
            "set_friend_add_request_async",
            "set_group_add_request_async"
        ];
    }

    public static function getLoggedAPIs() {
        return [
            "send_private_msg",
            "send_group_msg",
            "send_discuss_msg",
            "send_msg",
            "send_private_msg_async",
            "send_group_msg_async",
            "send_discuss_msg_async",
            "send_msg_async"
        ];
    }

    /**
     * @param WSConnection $connection
     * @param $reply
     * @param |null $function
     * @return bool
     */
    public static function processAPI($connection, $reply, $function = null) {
        $api_id = ZMBuf::$atomics["wait_msg_id"]->get();
        $reply["echo"] = $api_id;
        ZMBuf::$atomics["wait_msg_id"]->add(1);

        if (is_callable($function)) {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "func" => $function,
                "self_id" => $connection->getQQ()
            ]);
        } elseif ($function === true) {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "coroutine" => Co::getuid(),
                "self_id" => $connection->getQQ()
            ]);
        } else {
            ZMBuf::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "self_id" => $connection->getQQ()
            ]);
        }
        if ($connection->push(json_encode($reply))) {
            //Console::msg($reply, $connection->getQQ());
            ZMBuf::$atomics["out_count"]->add(1);
            if ($function === true) {
                Co::suspend();
                $data = ZMBuf::get("sent_api")[$api_id];
                ZMBuf::unsetByValue("sent_api", $reply["echo"]);
                return isset($data['result']) ? $data['result'] : null;
            }
            return true;
        } else {
            Console::warning("CQAPI send failed, websocket push error.");
            $response = [
                "status" => "failed",
                "retcode" => 999,
                "data" => null,
                "self_id" => $connection->getQQ()
            ];
            $s = ZMBuf::get("sent_api")[$reply["echo"]];
            if (($s["func"] ?? null) !== null)
                call_user_func($s["func"], $response, $reply);
            ZMBuf::unsetByValue("sent_api", $reply["echo"]);
            if ($function === true) return null;
            return false;
        }
    }
}
