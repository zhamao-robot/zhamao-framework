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

    public function __construct(Framework $framework){
        $this->starttime = microtime(true);
        $this->framework = $framework;
    }

    public function execute($it){
        $this->data = $it;
        if ($it["post_type"] == "message") {
            try {
                $this->callTask($it);
            } catch (\Exception $e) {
                CQUtil::errorLog("请求执行任务时异常\n" . $e->getMessage());
                CQUtil::sendDebugMsg("引起异常的消息：\n" . $it["message"]);
            }
        }
    }

    public function callTask($it){
        if ($this->data["post_type"] == "message") {
            foreach(Buffer::get("mods") as $v){
                Console::info("Activating module ".$v);
                /** @var ModBase $w */
                $w = new $v($this, $this->data);
                if($w->call_task === false){
                    $msg = trim($this->data["message"]);
                    $msg = explode(" ", $msg);
                    $prefix = Buffer::get("cmd_prefix");
                    if($prefix != ""){
                        if(mb_substr($msg[0],0,mb_strlen($prefix)) == $prefix){
                            $msg[0] = mb_substr($msg[0], mb_strlen($prefix));
                        }
                    }
                    $w->execute($msg);
                }
            }
        }
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
                if (CQUtil::APIPush($reply)) {
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
        CQUtil::sendGroupMsg($groupId, $msg);
    }

    public function sendPrivateMsg($userId, $msg){
        $this->function_called = true;
        CQUtil::sendPrivateMsg($userId, $msg);
    }

    public function isAdmin($user){
        if (in_array($user, Buffer::get("su"))) return true;
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
}