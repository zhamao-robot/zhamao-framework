<?php


namespace ZM\API;


use Framework\Console;
use ZM\Utils\ZMUtil;

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
     * 发送emoji表情
     * @param $id
     * @return string
     */
    public static function emoji($id) {
        if (is_numeric($id)) {
            return "[CQ:emoji,id=" . $id . "]";
        }
        Console::warning("传入的emoji id($id)错误！");
        return " ";
    }

    /**
     * 发送原创表情，存放在酷Q目录的data/bface/下
     * @param $id
     * @return string
     */
    public static function bface($id) {
        return "[CQ:bface,id=" . $id . "]";
    }

    /**
     * 发送小表情
     * @param $id
     * @return string
     */
    public static function sface($id) {
        if (is_numeric($id)) {
            return "[CQ:sface,id=" . $id . "]";
        }
        Console::warning("传入的sface id($id)错误！");
        return " ";
    }

    /**
     * 发送图片
     * cache为<FALSE>时禁用CQ-HTTP-API插件的缓存
     * @param $file
     * @param bool $cache
     * @return string
     */
    public static function image($file, $cache = true) {
        if ($cache === false)
            return "[CQ:image,file=" . $file . ",cache=0]";
        else
            return "[CQ:image,file=" . $file . "]";
    }

    /**
     * 发送语音
     * cache为<FALSE>时禁用CQ-HTTP-API插件的缓存
     * magic为<TRUE>时标记为变声
     * @param $file
     * @param bool $magic
     * @param bool $cache
     * @return string
     */
    public static function record($file, $magic = false, $cache = true) {
        if ($cache === false) $c = ",cache=0";
        else $c = "";
        if ($magic === true) $m = ",magic=true";
        else $m = "";
        return "[CQ:record,file=" . $file . $c . $m . "]";
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
     * @param string $audio
     * @param string $title
     * @param string $content
     * @param string $image
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
        while (($cq = ZMUtil::getCQ($msg)) !== null) {
            $msg = str_replace(mb_substr($msg, $cq["start"], $cq["end"] - $cq["start"] + 1), "", $msg);
        }
        return $msg;
    }
}
