<?php


namespace ZM\API;

use Co;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Event\EventHandler;
use ZM\Store\ZMBuf;

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
trait CQAPI
{
    /**
     * @param ConnectionObject $connection
     * @param $reply
     * @param |null $function
     * @return bool|array
     */
    private function processAPI($connection, $reply, $function = null) {
        $api_id = ZMBuf::$atomics["wait_msg_id"]->get();
        $reply["echo"] = $api_id;
        ZMBuf::$atomics["wait_msg_id"]->add(1);
        EventHandler::callCQAPISend($reply, $connection);
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
                "retcode" => -1000,
                "data" => null,
                "self_id" => $connection->getQQ()
            ];
            $s = ZMBuf::get("sent_api")[$reply["echo"]];
            if (($s["func"] ?? null) !== null)
                call_user_func($s["func"], $response, $reply);
            ZMBuf::unsetByValue("sent_api", $reply["echo"]);
            if ($function === true) return $response;
            return false;
        }
    }
}
