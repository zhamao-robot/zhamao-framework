<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use ZM\Annotation\CQ\CQCommand;
use ZM\API\CQ;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Entity\MatchResult;
use ZM\Event\EventManager;
use ZM\Requests\ZMRequest;
use ZM\Utils\Manager\ProcessManager;

class MessageUtil
{
    /**
     * 下载消息中 CQ 码的所有图片，通过 url
     * @param $msg
     * @param null $path
     * @return array|false
     */
    public static function downloadCQImage($msg, $path = null) {
        $path = $path ?? DataProvider::getDataFolder() . "images/";
        if (!is_dir($path)) @mkdir($path);
        $path = realpath($path);
        if ($path === false) {
            Console::warning(zm_internal_errcode("E00059") . "指定的路径错误不存在！");
            return false;
        }
        $files = [];
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == "image") {
                $result = ZMRequest::downloadFile($v->params["url"], $path . "/" . $v->params["file"]);
                if ($result === false) {
                    Console::warning(zm_internal_errcode("E00060") . "图片 " . $v->params["url"] . " 下载失败！");
                    return false;
                }
                $files[] = $path . "/" . $v->params["file"];
            }
        }
        return $files;
    }

    /**
     * 检查消息中是否含有图片 CQ 码
     * @param $msg
     * @return bool
     */
    public static function containsImage($msg): bool {
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == "image") {
                return true;
            }
        }
        return false;
    }

    public static function isAtMe($msg, $me_id): bool {
        return strpos($msg, CQ::at($me_id)) !== false;
    }

    /**
     * 通过本地地址返回图片的 CQ 码
     * type == 0 : 返回图片的 base64 CQ 码
     * type == 1 : 返回图片的 file://路径 CQ 码（路径必须为绝对路径）
     * type == 2 : 返回图片的 http://xxx CQ 码（默认为 /images/ 路径就是文件对应所在的目录）
     * @param $file
     * @param int $type
     * @return string
     */
    public static function getImageCQFromLocal($file, int $type = 0): string {
        switch ($type) {
            case 0:
                return CQ::image("base64://" . base64_encode(file_get_contents($file)));
            case 1:
                return CQ::image("file://" . $file);
            case 2:
                $info = pathinfo($file);
                return CQ::image(ZMConfig::get("global", "http_reverse_link") . "/images/" . $info["basename"]);
        }
        return "";
    }

    /**
     * 分割字符，将用户消息通过空格或换行分割为数组
     * @param $msg
     * @return array|string[]
     */
    public static function splitCommand($msg): array {
        $word = explodeMsg(str_replace("\r", "", $msg));
        if (empty($word)) $word = [""];
        if (count(explode("\n", $word[0])) >= 2) {
            $enter = explode("\n", $msg);
            $first = split_explode(" ", array_shift($enter));
            $word = array_merge($first, $enter);
            foreach ($word as $k => $v) {
                $word[$k] = trim($v);
            }
        }
        return $word;
    }

    /**
     * @param $msg
     * @param $obj
     * @return MatchResult
     */
    public static function matchCommand($msg, $obj): MatchResult {
        $ls = EventManager::$events[CQCommand::class] ?? [];
        if (is_array($msg)) {
            $msg = self::arrayToStr($msg);
        }
        $word = self::splitCommand($msg);
        $matched = new MatchResult();
        foreach ($ls as $v) {
            if (array_diff([$v->match, $v->pattern, $v->regex, $v->keyword, $v->end_with, $v->start_with], [""]) == []) continue;
            elseif (($v->user_id == 0 || ($v->user_id == $obj["user_id"])) &&
                ($v->group_id == 0 || ($v->group_id == ($obj["group_id"] ?? 0))) &&
                ($v->message_type == '' || ($v->message_type == $obj["message_type"]))
            ) {
                if (($word[0] != "" && $v->match == $word[0]) || in_array($word[0], $v->alias)) {
                    array_shift($word);
                    $matched->match = $word;
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->start_with != "" && mb_substr($msg, 0, mb_strlen($v->start_with)) === $v->start_with) {
                    $matched->match = [mb_substr($msg, mb_strlen($v->start_with))];
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->end_with != "" && mb_substr($msg, 0 - mb_strlen($v->end_with)) === $v->end_with) {
                    $matched->match = [substr($msg, 0, strripos($msg, $v->end_with))];
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->keyword != "" && mb_strpos($msg, $v->keyword) !== false) {
                    $matched->match = explode($v->keyword, $msg);
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->pattern != "") {
                    $match = matchArgs($v->pattern, $msg);
                    if ($match !== false) {
                        $matched->match = $match;
                        $matched->object = $v;
                        $matched->status = true;
                        break;
                    }
                } elseif ($v->regex != "") {
                    if (preg_match("/" . $v->regex . "/u", $msg, $word2) != 0) {
                        $matched->match = $word2;
                        $matched->object = $v;
                        $matched->status = true;
                        break;
                    }
                }
            }
        }
        return $matched;
    }

    public static function addShortCommand($command, string $reply) {
        for ($i = 0; $i < ZM_WORKER_NUM; ++$i) {
            ProcessManager::sendActionToWorker($i, "add_short_command", [$command, $reply]);
        }
    }

    /**
     * 字符串转数组
     * @param $msg
     * @param bool $ignore_space
     * @param false $trim_text
     * @return array
     */
    public static function strToArray($msg, bool $ignore_space = true, bool $trim_text = false): array {
        $arr = [];
        while (($rear = mb_strstr($msg, '[CQ:')) !== false && ($end = mb_strstr($rear, ']', true)) !== false) {
            // 把 [CQ: 前面的文字生成段落
            $front = mb_strstr($msg, '[CQ:', true);
            // 如果去掉空格都还有文字，或者不去掉空格有字符，且不忽略空格，则生成段落，否则不生成
            if (($trim_front = trim($front)) !== '' || ($front !== '' && !$ignore_space)) {
                $arr[] = ['type' => 'text', 'data' => ['text' => CQ::decode($trim_text ? $trim_front : $front)]];
            }
            // 处理 CQ 码
            $content = mb_substr($end, 4);
            $cq = explode(",", $content);
            $object_type = array_shift($cq);
            $object_params = [];
            foreach ($cq as $v) {
                $key = mb_strstr($v, "=", true);
                $object_params[$key] = CQ::decode(mb_substr(mb_strstr($v, "="), 1), true);
            }
            $arr[] = ["type" => $object_type, "data" => $object_params];
            $msg = mb_substr(mb_strstr($rear, ']'), 1);
        }
        if (($trim_msg = trim($msg)) !== '' || ($msg !== '' && !$ignore_space)) {
            $arr[] = ['type' => 'text', 'data' => ['text' => CQ::decode($trim_text ? $trim_msg : $msg)]];
        }
        return $arr;
    }

    /**
     * 数组转字符串
     * 纪念一下，这段代码完全由AI生成，没有人知道它是怎么写的，这句话是我自己写的，不知道是不是有人知道的
     * @param array $array
     * @return string
     * @author Copilot
     */
    public static function arrayToStr(array $array): string {
        $str = "";
        foreach ($array as $v) {
            if ($v['type'] == 'text') {
                $str .= $v['data']['text'];
            } else {
                $str .= "[CQ:" . $v['type'];
                foreach ($v['data'] as $key => $value) {
                    $str .= "," . $key . "=" . CQ::encode($value, true);
                }
                $str .= "]";
            }
        }
        return $str;
    }
}