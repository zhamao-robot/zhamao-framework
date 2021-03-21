<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use ZM\Annotation\CQ\CQCommand;
use ZM\API\CQ;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Entity\MatchResult;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Requests\ZMRequest;

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
        if (!is_dir($path)) mkdir($path);
        $path = realpath($path);
        if ($path === false) {
            Console::warning("指定的路径错误不存在！");
            return false;
        }
        $files = [];
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == "image") {
                $result = ZMRequest::downloadFile($v->params["url"], $path . "/" . $v->params["file"]);
                if ($result === false) {
                    Console::warning("图片 " . $v->params["url"] . " 下载失败！");
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

    public static function isAtMe($msg, $me_id) {
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
    public static function getImageCQFromLocal($file, $type = 0): string {
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
    public static function splitCommand($msg) {
        $word = explodeMsg(str_replace("\r", "", $msg));
        if (empty($word)) $word = [""];
        if (count(explode("\n", $word[0])) >= 2) {
            $enter = explode("\n", $msg);
            $first = split_explode(" ", array_shift($enter));
            $word = array_merge($first, $enter);
            foreach ($word as $k => $v) {
                $word[$k] = trim($word[$k]);
            }
        }
        return $word;
    }

    /**
     * @param $msg
     * @param $obj
     * @return MatchResult
     */
    public static function matchCommand($msg, $obj) {
        $ls = EventManager::$events[CQCommand::class];
        $word = self::splitCommand($msg);
        $matched = new MatchResult();
        foreach ($ls as $k => $v) {
            if (array_diff([$v->match, $v->pattern, $v->regex, $v->keyword, $v->end_with, $v->start_with], [""]) == []) continue;
            elseif (($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == $obj["user_id"])) &&
                ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == ($obj["group_id"] ?? 0))) &&
                ($v->message_type == '' || ($v->message_type != '' && $v->message_type == $obj["message_type"]))
            ) {
                if (($word[0] != "" && $v->match == $word[0]) || in_array($word[0], $v->alias)) {
                    array_shift($word);
                    $matched->match = $word;
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->start_with != "" && mb_strpos($msg, $v->start_with) === 0) {
                    $matched->match = [mb_substr($msg, mb_strlen($v->start_with))];
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                } elseif ($v->end_with != "" && strlen($msg) == (strripos($msg, $v->end_with) + strlen($v->end_with))) {
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
}