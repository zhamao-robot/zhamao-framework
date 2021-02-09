<?php


namespace ZM\API;


use ZM\Console\Console;

class CQ
{
    /**
     * at一下QQ用户（仅在QQ群支持at全体）
     * @param $qq
     * @return string
     */
    public static function at($qq) {
        if (is_numeric($qq) || $qq === "all") {
            return "[CQ:at,qq=" . $qq . "]";
        }
        Console::warning("传入的QQ号码($qq)错误！");
        return " ";
    }

    /**
     * 发送QQ原生表情
     * @param $id
     * @return string
     */
    public static function face($id) {
        if (is_numeric($id)) {
            return "[CQ:face,id=" . $id . "]";
        }
        Console::warning("传入的face id($id)错误！");
        return " ";
    }

    /**
     * 发送图片
     * @param $file
     * @param bool $cache
     * @param bool $flash
     * @param bool $proxy
     * @param int $timeout
     * @return string
     */
    public static function image($file, $cache = true, $flash = false, $proxy = true, $timeout = -1) {
        return
            "[CQ:image,file=" . $file .
            (!$cache ? ",cache=0" : "") .
            ($flash ? ",type=flash" : "") .
            (!$proxy ? ",proxy=false" : "") .
            ($timeout != -1 ? (",timeout=" . $timeout) : "") .
            "]";
    }

    /**
     * 发送语音
     * @param $file
     * @param bool $magic
     * @param bool $cache
     * @param bool $proxy
     * @param int $timeout
     * @return string
     */
    public static function record($file, $magic = false, $cache = true, $proxy = true, $timeout = -1) {
        return
            "[CQ:record,file=" . $file .
            (!$cache ? ",cache=0" : "") .
            ($magic ? ",magic=1" : "") .
            (!$proxy ? ",proxy=false" : "") .
            ($timeout != -1 ? (",timeout=" . $timeout) : "") .
            "]";
    }

    /**
     * 发送短视频
     * @param $file
     * @param bool $cache
     * @param bool $proxy
     * @param int $timeout
     * @return string
     */
    public static function video($file, $cache = true, $proxy = true, $timeout = -1) {
        return
            "[CQ:video,file=" . $file .
            (!$cache ? ",cache=0" : "") .
            (!$proxy ? ",proxy=false" : "") .
            ($timeout != -1 ? (",timeout=" . $timeout) : "") .
            "]";
    }

    /**
     * 发送投掷骰子（只能在单条回复中单独使用）
     * @return string
     */
    public static function rps() {
        return "[CQ:rps]";
    }

    /**
     * 发送掷骰子表情（只能在单条回复中单独使用）
     * @return string
     */
    public static function dice() {
        return "[CQ:dice]";
    }

    /**
     * 戳一戳（原窗口抖动，仅支持好友消息使用）
     * @return string
     */
    public static function shake() {
        return "[CQ:shake]";
    }

    /**
     * 发送新的戳一戳
     * @param $type
     * @param $id
     * @param string $name
     * @return string
     */
    public static function poke($type, $id, $name = "") {
        return "[CQ:poke,type=$type,id=$id" . ($name != "" ? ",name=$name" : "") . "]";
    }

    /**
     * 发送匿名消息
     * @param int $ignore
     * @return string
     */
    public static function anonymous($ignore = 1) {
        return "[CQ:anonymous" . ($ignore != 1 ? ",ignore=0" : "") . "]";
    }

    /**
     * 发送链接分享（只能在单条回复中单独使用）
     * @param $url
     * @param $title
     * @param null $content
     * @param null $image
     * @return string
     */
    public static function share($url, $title, $content = null, $image = null) {
        if ($content === null) $c = "";
        else $c = ",content=" . $content;
        if ($image === null) $i = "";
        else $i = ",image=" . $image;
        return "[CQ:share,url=" . $url . ",title=" . $title . $c . $i . "]";
    }

    /**
     * 发送好友或群推荐名片
     * @param $type
     * @param $id
     * @return string
     */
    public static function contact($type, $id) {
        return "[CQ:contact,type=$type,id=$id]";
    }

    public static function location($lat, $lon, $title = "", $content = "") {

    }

    /**
     * 发送音乐分享（只能在单条回复中单独使用）
     * qq、163、xiami为内置分享，需要先通过搜索功能获取id后使用
     * custom为自定义分享
     * 当为自定义分享时：
     *  $id_or_url 为音乐卡片点进去打开的链接（一般是音乐介绍界面啦什么的）
     *  $audio 为音乐（如mp3文件）的HTTP链接地址（不可为空）
     *  $title 为音乐卡片的标题，建议12字以内（不可为空）
     *  $content 为音乐卡片的简介（可忽略）
     *  $image 为音乐卡片的图片链接地址（可忽略）
     * @param $type
     * @param $id_or_url
     * @param null $audio
     * @param null $title
     * @param null $content
     * @param null $image
     * @return string
     */
    public static function music($type, $id_or_url, $audio = null, $title = null, $content = null, $image = null) {
        switch ($type) {
            case "qq":
            case "163":
            case "xiami":
                return "[CQ:music,type=$type,id=$id_or_url]";
            case "custom":
                if ($title === null || $audio === null) {
                    Console::warning("传入CQ码实例的标题和音频链接不能为空！");
                    return " ";
                }
                if ($content === null) $c = "";
                else $c = ",content=" . $content;
                if ($image === null) $i = "";
                else $i = ",image=" . $image;
                return "[CQ:music,type=custom,url=" . $id_or_url . ",audio=" . $audio . ",title=" . $title . $c . $i . "]";
            default:
                Console::warning("传入的music type($type)错误！");
                return " ";
        }
    }

    public static function forward($id) {
        return "[CQ:forward,id=$id]";
    }

    public static function node($user_id, $nickname, $content) {
        return "[CQ:node,user_id=$user_id,nickname=$nickname,content=" . self::escape($content) . "]";
    }

    /**
     * 反转义字符串中的CQ码敏感符号
     * @param $str
     * @return mixed
     */
    public static function decode($str) {
        $str = str_replace("&amp;", "&", $str);
        $str = str_replace("&#91;", "[", $str);
        $str = str_replace("&#93;", "]", $str);
        return $str;
    }

    public static function replace($str) {
        $str = str_replace("{{", "[", $str);
        $str = str_replace("}}", "]", $str);
        return $str;
    }

    /**
     * 转义CQ码
     * @param $msg
     * @return mixed
     */
    public static function escape($msg) {
        $msg = str_replace("&", "&amp;", $msg);
        $msg = str_replace("[", "&#91;", $msg);
        $msg = str_replace("]", "&#93;", $msg);
        return $msg;
    }

    public static function encode($str) {
        return self::escape($str);
    }

    public static function removeCQ($msg) {
        while (($cq = self::getCQ($msg)) !== null) {
            $msg = str_replace(mb_substr($msg, $cq["start"], $cq["end"] - $cq["start"] + 1), "", $msg);
        }
        return $msg;
    }

    public static function getCQ($msg) {
        if (($start = mb_strpos($msg, '[')) === false) return null;
        if (($end = mb_strpos($msg, ']')) === false) return null;
        $msg = mb_substr($msg, $start + 1, $end - $start - 1);
        if (mb_substr($msg, 0, 3) != "CQ:") return null;
        $msg = mb_substr($msg, 3);
        $msg2 = explode(",", $msg);
        $type = array_shift($msg2);
        $array = [];
        foreach ($msg2 as $k => $v) {
            $ss = explode("=", $v);
            $sk = array_shift($ss);
            $array[$sk] = implode("=", $ss);
        }
        return ["type" => $type, "params" => $array, "start" => $start, "end" => $end];
    }
}
