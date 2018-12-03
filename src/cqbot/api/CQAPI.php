<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/11/26
 * Time: 9:23 AM
 */

/**
 * Class CQAPI
 * @method static send_private_msg($self_id, $params, callable $function = null)
 * @method static send_group_msg($self_id, $params, callable $function = null)
 * @method static send_discuss_msg($self_id, $params, callable $function = null)
 * @method static send_msg($self_id, $params, callable $function = null)
 * @method static delete_msg($self_id, $params, callable $function = null)
 * @method static send_like($self_id, $params, callable $function = null)
 * @method static set_group_kick($self_id, $params, callable $function = null)
 * @method static set_group_ban($self_id, $params, callable $function = null)
 * @method static set_group_anonymous_ban($self_id, $params, callable $function = null)
 * @method static set_group_whole_ban($self_id, $params, callable $function = null)
 * @method static set_group_admin($self_id, $params, callable $function = null)
 * @method static set_group_anonymous($self_id, $params, callable $function = null)
 * @method static set_group_card($self_id, $params, callable $function = null)
 * @method static set_group_leave($self_id, $params, callable $function = null)
 * @method static set_group_special_title($self_id, $params, callable $function = null)
 * @method static set_discuss_leave($self_id, $params, callable $function = null)
 * @method static set_friend_add_request($self_id, $params, callable $function = null)
 * @method static set_group_add_request($self_id, $params, callable $function = null)
 * @method static get_login_info($self_id, $params, callable $function = null)
 * @method static get_stranger_info($self_id, $params, callable $function = null)
 * @method static get_group_list($self_id, $params, callable $function = null)
 * @method static get_group_member_info($self_id, $params, callable $function = null)
 * @method static get_group_member_list($self_id, $params, callable $function = null)
 * @method static get_cookies($self_id, $params, callable $function = null)
 * @method static get_csrf_token($self_id, $params, callable $function = null)
 * @method static get_credentials($self_id, $params, callable $function = null)
 * @method static get_record($self_id, $params, callable $function = null)
 * @method static get_status($self_id, $params, callable $function = null)
 * @method static get_version_info($self_id, $params, callable $function = null)
 * @method static set_restart($self_id, $params, callable $function = null)
 * @method static set_restart_plugin($self_id, $params, callable $function = null)
 * @method static clean_data_dir($self_id, $params, callable $function = null)
 * @method static clean_plugin_log($self_id, $params, callable $function = null)
 * @method static _get_friend_list($self_id, $params, callable $function = null)
 * @method static _get_group_info($self_id, $params, callable $function = null)
 * @method static _get_vip_info($self_id, $params, callable $function = null)
 * @method static send_private_msg_async($self_id, $params, callable $function = null)
 * @method static send_group_msg_async($self_id, $params, callable $function = null)
 * @method static send_discuss_msg_async($self_id, $params, callable $function = null)
 * @method static send_msg_async($self_id, $params, callable $function = null)
 * @method static delete_msg_async($self_id, $params, callable $function = null)
 * @method static set_group_kick_async($self_id, $params, callable $function = null)
 * @method static set_group_ban_async($self_id, $params, callable $function = null)
 * @method static set_group_anonymous_ban_async($self_id, $params, callable $function = null)
 * @method static set_group_whole_ban_async($self_id, $params, callable $function = null)
 * @method static set_group_admin_async($self_id, $params, callable $function = null)
 * @method static set_group_anonymous_async($self_id, $params, callable $function = null)
 * @method static set_group_card_async($self_id, $params, callable $function = null)
 * @method static set_group_leave_async($self_id, $params, callable $function = null)
 * @method static set_group_special_title_async($self_id, $params, callable $function = null)
 * @method static set_discuss_leave_async($self_id, $params, callable $function = null)
 * @method static set_friend_add_request_async($self_id, $params, callable $function = null)
 * @method static set_group_add_request_async($self_id, $params, callable $function = null)
 */
class CQAPI
{
    public static function debug($msg, $head = null, $self_id = null) {
        if($head === null) $msg = date("[H:i:s") . " DEBUG] ".$msg;
        if($self_id === null) $self_id = CQUtil::findRobot();
        else $msg = $head.$msg;
        if($self_id !== null){
            return self::send_group_msg($self_id, ["message" => $msg, "group_id" => Cache::get("admin_group")]);
        }
        return false;
    }

    public static function __callStatic($name, $arg) {
        if(mb_substr($name, -6) == "_after"){
            $all = self::getSupportedAPIs();
            $find = null;
            $true_name = mb_substr($name, 0, -6);
            if(!in_array($true_name, $all)){
                Console::error("Unknown API " . $name);
                return false;
            }
            $ms = array_shift($arg);
            Scheduler::after($ms, function() use ($true_name, $arg){
                $reply = ["action" => $true_name];
                if (!is_array($arg[1])) {
                    Console::error("Error when parsing params. Please make sure your params is an array.");
                    return false;
                }
                if ($arg[1] != []) {
                    $reply["params"] = $arg[1];
                }
                return self::processAPI($arg[0], $reply, $arg[2]);
            });
            return true;
        }
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
            Console::error("Unknown API " . $name);
            return false;
        }
        $reply = ["action" => $find];
        if (!is_array($arg[1])) {
            Console::error("Error when parsing params. Please make sure your params is an array.");
            return false;
        }
        if ($arg[1] != []) {
            $reply["params"] = $arg[1];
        }
        return self::processAPI($arg[0], $reply, $arg[2]);
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
     * @param $self_id
     * @param $reply
     * @param callable|null $function
     * @return bool
     */
    private static function processAPI($self_id, $reply, callable $function = null) {
        $api_id = Cache::$api_id->get();
        $reply["echo"] = $api_id;
        Cache::$api_id->add(1);
        if ($self_id instanceof RobotWSConnection) {
            $connection = $self_id;
            $self_id = $connection->getQQ();
        } else $connection = ConnectionManager::getRobotConnection($self_id);
        if ($connection instanceof NullConnection) {
            Console::error("未找到qq号：" . $self_id . "的API连接");
            return false;
        }
        if ($connection->push(json_encode($reply))) {
            Cache::appendKey("sent_api", $api_id, [
                "data" => $reply,
                "time" => microtime(true),
                "func" => $function,
                "self_id" => $self_id
            ]);
            if (in_array($reply["action"], self::getLoggedAPIs())) {
                Console::msg($reply);
                Cache::$out_count->add(1);
            }
            return true;
        } else return false;
    }
}