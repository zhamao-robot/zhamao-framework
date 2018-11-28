<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:43
 */

class CQBot
{
    /** @var Framework */
    public $framework;

    //传入数据
    public $data = null;

    //检测有没有回复过消息
    private $function_called = false;

    public $starttime;
    public $endtime;
    public $self_id;
    public $circle;

    public function __construct(Framework $framework, $circle, $package) {
        $this->circle = $circle;
        $this->starttime = microtime(true);
        $this->framework = $framework;
        $this->data = $package;
        $this->self_id = $this->data["self_id"];
    }

    public function execute() {
        if ($this->circle >= 5) return false;
        if ($this->data === null) return false;
        if (isset($it["user_id"]) && CQUtil::isRobot($this->data["user_id"])) return false;
        if (isset($it["group_id"]) && $this->data["group_id"] == Cache::get("admin_group")) {
            if ($this->getRobotId() != Cache::get("admin_active")) {
                return false;
            }
        }
        if ($this->data["message"] == "")
            return false;
        foreach (Cache::get("mods") as $v) {
            /** @var ModBase $r */
            $r = new $v($this, $this->data);
            if ($r->split_execute === true) {
                $msg = trim($this->data["message"]);
                $msg = explodeMsg($msg);
                $r->execute($msg);
            }
        }
        $this->endtime = microtime(true);
        return $this->function_called;
    }

    /**
     * 快速回复消息
     * @param $msg
     * @param callable|null $callback
     * @param bool $async
     * @return bool
     */
    public function reply($msg, callable $callback = null, $async = false) {
        $this->function_called = true;
        switch ($this->data["message_type"]) {
            case "group":
                $this->function_called = true;
                if (!$async) return CQAPI::send_group_msg($this->getRobotId(), ["group_id" => $this->data["group_id"], "message" => $msg], $callback);
                else return CQAPI::send_group_msg_async($this->getRobotId(), ["group_id" => $this->data["group_id"], "message" => $msg], $callback);
            case "private":
                $this->function_called = true;
                if (!$async) return CQAPI::send_private_msg($this->getRobotId(), ["user_id" => $this->data["user_id"], "message" => $msg], $callback);
                else return CQAPI::send_private_msg_async($this->getRobotId(), ["user_id" => $this->data["user_id"], "message" => $msg], $callback);
            case "discuss":
                $this->function_called = true;
                if (!$async) return CQAPI::send_discuss_msg($this->getRobotId(), ["discuss_id" => $this->data["discuss_id"], "message" => $msg], $callback);
                else return CQAPI::send_discuss_msg_async($this->getRobotId(), ["discuss_id" => $this->data["discuss_id"], "message" => $msg], $callback);
            case "wechat":
                //TODO: add wechat account support in the future
                break;
        }
        return false;
    }

    public function isAdmin($user) {
        if (in_array($user, Cache::get("admin"))) return true;
        else return false;
    }

    public function replace($msg, $dat) {
        $msg = str_replace("{at}", '[CQ:at,qq=' . $dat["user_id"] . ']', $msg);
        $msg = str_replace("{and}", '&', $msg);
        while (strpos($msg, '{') !== false && strpos($msg, '}') !== false) {
            if (strpos($msg, '{') > strpos($msg, '}')) return $msg;
            $start = strpos($msg, '{');
            $end = strpos($msg, '}');
            $sub = explode("=", substr($msg, $start + 1, $end - $start - 1));
            switch ($sub[0]) {
                case "at":
                    $qq = $sub[1];
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), '[CQ:at,qq=' . $qq . ']', $msg);
                    break;
                case "image":
                case "record":
                    $pictFile = $sub[1];
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), '[CQ:' . $sub[0] . ',file=' . $pictFile . ']', $msg);
                    break;
                case "dice":
                    $file = $sub[1];
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), '[CQ:dice,type=' . $file . ']', $msg);
                    break;
                case "shake":
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), '[CQ:shake]', $msg);
                    break;
                case "music":
                    $id = $sub[1];
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), '[CQ:music,type=163,id=' . $id . ']', $msg);
                    break;
                case "internet":
                    array_shift($sub);
                    $id = implode("=", $sub);
                    if (substr($id, 0, 7) != "http://") $id = "http://" . $id;
                    $is = file_get_contents($id, false, NULL, 0, 1024);
                    if ($is == false) $is = "[请求时发生了错误] 如有疑问，请联系管理员";
                    $msg = str_replace(substr($msg, $start, $end - $start + 1), $is, $msg);
                    break 2;
                default:
                    break 2;
            }
        }
        return $msg;
    }

    /**
     * 返回当前机器人的id
     * @return string|null
     */
    public function getRobotId() {
        return $this->data["self_id"] ?? null;
    }
}