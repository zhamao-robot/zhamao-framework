<?php

declare(strict_types=1);

namespace ZM\API;

use ZM\Console\Console;
use ZM\Entity\CQObject;

class CQ
{
    /**
     * at一下QQ用户（仅在QQ群支持at全体）
     * @param $qq
     * @return string
     */
    public static function at($qq)
    {
        if (is_numeric($qq) || $qq === 'all') {
            return '[CQ:at,qq=' . $qq . ']';
        }
        Console::warning(zm_internal_errcode('E00035') . "传入的QQ号码({$qq})错误！");
        return ' ';
    }

    /**
     * 发送QQ原生表情
     * @param $id
     * @return string
     */
    public static function face($id)
    {
        if (is_numeric($id)) {
            return '[CQ:face,id=' . $id . ']';
        }
        Console::warning(zm_internal_errcode('E00035') . "传入的face id({$id})错误！");
        return ' ';
    }

    /**
     * 发送图片
     * @param $file
     * @return string
     */
    public static function image($file, bool $cache = true, bool $flash = false, bool $proxy = true, int $timeout = -1)
    {
        return
            '[CQ:image,file=' . self::encode($file, true) .
            (!$cache ? ',cache=0' : '') .
            ($flash ? ',type=flash' : '') .
            (!$proxy ? ',proxy=false' : '') .
            ($timeout != -1 ? (',timeout=' . $timeout) : '') .
            ']';
    }

    /**
     * 发送语音
     * @param $file
     * @return string
     */
    public static function record($file, bool $magic = false, bool $cache = true, bool $proxy = true, int $timeout = -1)
    {
        return
            '[CQ:record,file=' . self::encode($file, true) .
            (!$cache ? ',cache=0' : '') .
            ($magic ? ',magic=1' : '') .
            (!$proxy ? ',proxy=false' : '') .
            ($timeout != -1 ? (',timeout=' . $timeout) : '') .
            ']';
    }

    /**
     * 发送短视频
     * @param $file
     * @return string
     */
    public static function video($file, bool $cache = true, bool $proxy = true, int $timeout = -1)
    {
        return
            '[CQ:video,file=' . self::encode($file, true) .
            (!$cache ? ',cache=0' : '') .
            (!$proxy ? ',proxy=false' : '') .
            ($timeout != -1 ? (',timeout=' . $timeout) : '') .
            ']';
    }

    /**
     * 发送投掷骰子（只能在单条回复中单独使用）
     * @return string
     */
    public static function rps()
    {
        return '[CQ:rps]';
    }

    /**
     * 发送掷骰子表情（只能在单条回复中单独使用）
     * @return string
     */
    public static function dice()
    {
        return '[CQ:dice]';
    }

    /**
     * 戳一戳（原窗口抖动，仅支持好友消息使用）
     * @return string
     */
    public static function shake()
    {
        return '[CQ:shake]';
    }

    /**
     * 发送新的戳一戳
     * @param $type
     * @param $id
     * @return string
     */
    public static function poke($type, $id, string $name = '')
    {
        return "[CQ:poke,type={$type},id={$id}" . ($name != '' ? (',name=' . self::encode($name, true)) : '') . ']';
    }

    /**
     * 发送匿名消息
     * @return string
     */
    public static function anonymous(int $ignore = 1)
    {
        return '[CQ:anonymous' . ($ignore != 1 ? ',ignore=0' : '') . ']';
    }

    /**
     * 发送链接分享（只能在单条回复中单独使用）
     * @param $url
     * @param $title
     * @param  null   $content
     * @param  null   $image
     * @return string
     */
    public static function share($url, $title, $content = null, $image = null)
    {
        if ($content === null) {
            $c = '';
        } else {
            $c = ',content=' . self::encode($content, true);
        }
        if ($image === null) {
            $i = '';
        } else {
            $i = ',image=' . self::encode($image, true);
        }
        return '[CQ:share,url=' . self::encode($url, true) . ',title=' . self::encode($title, true) . $c . $i . ']';
    }

    /**
     * 发送好友或群推荐名片
     * @param $type
     * @param $id
     * @return string
     */
    public static function contact($type, $id)
    {
        return "[CQ:contact,type={$type},id={$id}]";
    }

    /**
     * 发送位置
     * @param $lat
     * @param $lon
     * @return string
     */
    public static function location($lat, $lon, string $title = '', string $content = '')
    {
        return '[CQ:location' .
            ',lat=' . self::encode($lat, true) .
            ',lon=' . self::encode($lon, true) .
            ($title != '' ? (',title=' . self::encode($title, true)) : '') .
            ($content != '' ? (',content=' . self::encode($content, true)) : '') .
            ']';
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
     * @param  null   $audio
     * @param  null   $title
     * @param  null   $content
     * @param  null   $image
     * @return string
     */
    public static function music($type, $id_or_url, $audio = null, $title = null, $content = null, $image = null)
    {
        switch ($type) {
            case 'qq':
            case '163':
            case 'xiami':
                return "[CQ:music,type={$type},id={$id_or_url}]";
            case 'custom':
                if ($title === null || $audio === null) {
                    Console::warning(zm_internal_errcode('E00035') . '传入CQ码实例的标题和音频链接不能为空！');
                    return ' ';
                }
                if ($content === null) {
                    $c = '';
                } else {
                    $c = ',content=' . self::encode($content, true);
                }
                if ($image === null) {
                    $i = '';
                } else {
                    $i = ',image=' . self::encode($image, true);
                }
                return '[CQ:music,type=custom,url=' .
                    self::encode($id_or_url, true) .
                    ',audio=' . self::encode($audio, true) . ',title=' . self::encode($title, true) . $c . $i .
                    ']';
            default:
                Console::warning(zm_internal_errcode('E00035') . "传入的music type({$type})错误！");
                return ' ';
        }
    }

    public static function forward($id)
    {
        return '[CQ:forward,id=' . self::encode($id) . ']';
    }

    public static function node($user_id, $nickname, $content)
    {
        return "[CQ:node,user_id={$user_id},nickname=" . self::encode($nickname, true) . ',content=' . self::encode($content, true) . ']';
    }

    public static function xml($data)
    {
        return '[CQ:xml,data=' . self::encode($data, true) . ']';
    }

    public static function json($data, $resid = 0)
    {
        return '[CQ:json,data=' . self::encode($data, true) . ',resid=' . intval($resid) . ']';
    }

    public static function _custom(string $type_name, $params)
    {
        $code = '[CQ:' . $type_name;
        foreach ($params as $k => $v) {
            $code .= ',' . $k . '=' . self::escape($v, true);
        }
        $code .= ']';
        return $code;
    }

    /**
     * 反转义字符串中的CQ码敏感符号
     * @param mixed $msg
     * @param mixed $is_content
     */
    public static function decode($msg, $is_content = false)
    {
        $msg = str_replace(['&amp;', '&#91;', '&#93;'], ['&', '[', ']'], $msg);
        if ($is_content) {
            $msg = str_replace('&#44;', ',', $msg);
        }
        return $msg;
    }

    public static function replace($str)
    {
        $str = str_replace('{{', '[', $str);
        return str_replace('}}', ']', $str);
    }

    /**
     * 转义CQ码的特殊字符，同encode
     * @param mixed $msg
     * @param mixed $is_content
     */
    public static function escape($msg, $is_content = false)
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], $msg);
        if ($is_content) {
            $msg = str_replace(',', '&#44;', $msg);
        }
        return $msg;
    }

    /**
     * 转义CQ码的特殊字符
     * @param mixed $msg
     * @param mixed $is_content
     */
    public static function encode($msg, $is_content = false)
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], $msg);
        if ($is_content) {
            $msg = str_replace(',', '&#44;', $msg);
        }
        return $msg;
    }

    /**
     * 移除消息中所有的CQ码并返回移除CQ码后的消息
     * @param $msg
     * @return string
     */
    public static function removeCQ($msg)
    {
        $final = '';
        $last_end = 0;
        foreach (self::getAllCQ($msg) as $v) {
            $final .= mb_substr($msg, $last_end, $v['start'] - $last_end);
            $last_end = $v['end'] + 1;
        }
        $final .= mb_substr($msg, $last_end);
        return $final;
    }

    /**
     * 获取消息中第一个CQ码
     * @param mixed $msg
     * @param mixed $is_object
     */
    public static function getCQ($msg, $is_object = false)
    {
        if (($head = mb_strpos($msg, '[CQ:')) !== false) {
            $key_offset = mb_substr($msg, $head);
            $close = mb_strpos($key_offset, ']');
            if ($close === false) {
                return null;
            }
            $content = mb_substr($msg, $head + 4, $close + $head - mb_strlen($msg));
            $exp = explode(',', $content);
            $cq['type'] = array_shift($exp);
            foreach ($exp as $v) {
                $ss = explode('=', $v);
                $sk = array_shift($ss);
                $cq['params'][$sk] = self::decode(implode('=', $ss), true);
            }
            $cq['start'] = $head;
            $cq['end'] = $close + $head;
            return !$is_object ? $cq : CQObject::fromArray($cq);
        }
        return null;
    }

    /**
     * 获取消息中所有的CQ码
     * @param mixed $msg
     * @param mixed $is_object
     */
    public static function getAllCQ($msg, $is_object = false)
    {
        $cqs = [];
        $offset = 0;
        while (($head = mb_strpos(($submsg = mb_substr($msg, $offset)), '[CQ:')) !== false) {
            $key_offset = mb_substr($submsg, $head);
            $tmpmsg = mb_strpos($key_offset, ']');
            if ($tmpmsg === false) {
                break;
            } // 没闭合，不算CQ码
            $content = mb_substr($submsg, $head + 4, $tmpmsg + $head - mb_strlen($submsg));
            $exp = explode(',', $content);
            $cq = [];
            $cq['type'] = array_shift($exp);
            foreach ($exp as $v) {
                $ss = explode('=', $v);
                $sk = array_shift($ss);
                $cq['params'][$sk] = self::decode(implode('=', $ss), true);
            }
            $cq['start'] = $offset + $head;
            $cq['end'] = $offset + $tmpmsg + $head;
            $offset += $head + $tmpmsg + 1;
            $cqs[] = (!$is_object ? $cq : CQObject::fromArray($cq));
        }
        return $cqs;
    }
}
