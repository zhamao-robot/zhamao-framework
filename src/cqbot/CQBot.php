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
    public $current_id;
    public $circle;

    public function __construct(Framework $framework, $circle, $package) {
        $this->circle = $circle;
        $this->starttime = microtime(true);
        $this->framework = $framework;
        $this->data = $package;
        $this->current_id = $this->data["self_id"];
    }

    public function execute() {
        if ($this->circle >= 5) return false;
        if ($this->data === null) return false;
        if (isset($it["user_id"]) && CQUtil::isRobot($this->data["user_id"])) return false;
        if (isset($it["group_id"]) && $this->data["group_id"] == Buffer::get("admin_group")) {
            if ($this->getRobotId() != Buffer::get("admin_active")) {
                return false;
            }
        }
        if ($this->data["message"] == "")
            return false;
        foreach (Buffer::get("mods") as $v) {
            /** @var ModBase $r */
            $r = new $v($this, $this->data);
            if ($r->function_call === false) {
                $msg = trim($this->data["message"]);
                $msg = explode(" ", $msg);
                $r->execute($msg);
            }
        }
        $this->endtime = microtime(true);
        return $this->function_called;
    }

    public function reply($msg){
        $this->function_called = true;
        switch ($this->data["message_type"]) {
            case "group":
                $this->sendGroupMsg($this->data["group_id"], $msg);
                break;
            case "private":
                $this->sendPrivateMsg($this->data["user_id"], $msg);
                break;
            case "discuss":
                $reply = json_encode(["action" => "send_discuss_msg", "params" => ["discuss_id" => $this->data["discuss_id"], "message" => $msg]]);
                $connect = CQUtil::getApiConnectionByQQ($this->current_id);
                if (CQUtil::sendAPI($connect->fd, $reply, ["send_discuss_msg"])) {
                    $out_count = Buffer::$out_count->get();
                    if (Buffer::$data["info_level"] == 2) {
                        Console::put("************API PUSHED***********");
                    }
                    if (Buffer::$data["info_level"] >= 1) {
                        Console::put(Console::setColor(date("H:i:s "), "lightpurple") . Console::setColor("[$out_count]REPLY", "blue") . Console::setColor(" > ", "gray") . json_decode($reply, true)['params']["message"]);
                    }
                    Buffer::$out_count->add(1);
                }
                break;
            case "wechat":
                //TODO: add wechat account support in the future
                break;
        }
    }

    public function sendGroupMsg($groupId, $msg){
        $this->function_called = true;
        CQUtil::sendGroupMsg($groupId, $msg, $this->current_id);
    }

    public function sendPrivateMsg($userId, $msg){
        $this->function_called = true;
        CQUtil::sendPrivateMsg($userId, $msg, $this->current_id);
    }

    public function isAdmin($user){
        if (in_array($user, Buffer::get("admin"))) return true;
        else return false;
    }

    public function replace($msg, $dat){
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