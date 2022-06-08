<?php

declare(strict_types=1);

namespace ZM\API;

use Stringable;
use ZM\Entity\CQObject;

class CQ
{
    /**
     * at一下QQ用户（仅在QQ群支持at全体）
     * @param  int|string $qq 用户QQ号/ID号
     * @return string     CQ码
     */
    public static function at($qq): string
    {
        return self::buildCQ('at', ['qq' => $qq]);
    }

    /**
     * 发送QQ原生表情
     * @param  int|string $id 表情ID
     * @return string     CQ码
     */
    public static function face($id): string
    {
        return self::buildCQ('face', ['id' => $id]);
    }

    /**
     * 发送图片
     * @param  string $file    文件的路径、URL或者base64编码的图片数据
     * @param  bool   $cache   是否缓存（默认为true）
     * @param  bool   $flash   是否闪照（默认为false）
     * @param  bool   $proxy   是否使用代理（默认为true）
     * @param  int    $timeout 超时时间（默认不超时）
     * @return string CQ码
     */
    public static function image(string $file, bool $cache = true, bool $flash = false, bool $proxy = true, int $timeout = -1): string
    {
        $optional_values = [
            'cache' => !$cache ? 'cache=0' : '',
            'flash' => $flash ? 'type=flash' : '',
            'proxy' => !$proxy ? 'proxy=false' : '',
            'timeout' => $timeout != -1 ? 'timeout=' . $timeout : '',
        ];
        return self::buildCQ('image', ['file' => $file], $optional_values);
    }

    /**
     * 发送语音
     * @param  string $file    文件的路径、URL或者base64编码的语音数据
     * @param  bool   $magic   是否加特技（默认为false）
     * @param  bool   $cache   是否缓存（默认为true）
     * @param  bool   $proxy   是否使用代理（默认为true）
     * @param  int    $timeout 超时时间（默认不超时）
     * @return string CQ码
     */
    public static function record(string $file, bool $magic = false, bool $cache = true, bool $proxy = true, int $timeout = -1): string
    {
        $optional_values = [
            'magic' => $magic ? 'magic=true' : '',
            'cache' => !$cache ? 'cache=0' : '',
            'proxy' => !$proxy ? 'proxy=false' : '',
            'timeout' => $timeout != -1 ? 'timeout=' . $timeout : '',
        ];
        return self::buildCQ('record', ['file' => $file], $optional_values);
    }

    /**
     * 发送短视频
     * @param  string $file    文件的路径、URL或者base64编码的短视频数据
     * @param  bool   $cache   是否缓存（默认为true）
     * @param  bool   $proxy   是否使用代理（默认为true）
     * @param  int    $timeout 超时时间（默认不超时）
     * @return string CQ码
     */
    public static function video(string $file, bool $cache = true, bool $proxy = true, int $timeout = -1): string
    {
        $optional_values = [
            'cache' => !$cache ? 'cache=0' : '',
            'proxy' => !$proxy ? 'proxy=false' : '',
            'timeout' => $timeout != -1 ? 'timeout=' . $timeout : '',
        ];
        return self::buildCQ('video', ['file' => $file], $optional_values);
    }

    /**
     * 发送投掷骰子（只能在单条回复中单独使用）
     * @return string CQ码
     */
    public static function rps(): string
    {
        return '[CQ:rps]';
    }

    /**
     * 发送掷骰子表情（只能在单条回复中单独使用）
     * @return string CQ码
     */
    public static function dice(): string
    {
        return '[CQ:dice]';
    }

    /**
     * 戳一戳（原窗口抖动，仅支持好友消息使用）
     * @return string CQ码
     */
    public static function shake(): string
    {
        return '[CQ:shake]';
    }

    /**
     * 发送新的戳一戳
     * @param  int|string $type 焯一戳类型
     * @param  int|string $id   戳一戳ID号
     * @param  string     $name 戳一戳名称（可选）
     * @return string     CQ码
     */
    public static function poke($type, $id, string $name = ''): string
    {
        $optional_values = [
            'name' => $name ? 'name=' . $name : '',
        ];
        return self::buildCQ('poke', ['type' => $type, 'id' => $id], $optional_values);
    }

    /**
     * 发送匿名消息
     * @param  int    $ignore 是否忽略错误（默认为1，0表示不忽略错误）
     * @return string CQ码
     */
    public static function anonymous(int $ignore = 1): string
    {
        return self::buildCQ('anonymous', [], ['ignore' => $ignore != 1 ? 'ignore=0' : '']);
    }

    /**
     * 发送链接分享（只能在单条回复中单独使用）
     * @param  string      $url     分享地址
     * @param  string      $title   标题
     * @param  null|string $content 卡片内容（可选）
     * @param  null|string $image   卡片图片（可选）
     * @return string      CQ码
     */
    public static function share(string $url, string $title, ?string $content = null, ?string $image = null): string
    {
        $optional_values = [
            'content' => $content ? 'content=' . self::encode($content, true) : '',
            'image' => $image ? 'image=' . self::encode($image, true) : '',
        ];
        return self::buildCQ('share', ['url' => $url, 'title' => $title], $optional_values);
    }

    /**
     * 发送好友或群推荐名片
     * @param  int|string $type 名片类型
     * @param  int|string $id   好友或群ID
     * @return string     CQ码
     */
    public static function contact($type, $id): string
    {
        return self::buildCQ('contact', ['type' => $type, 'id' => $id]);
    }

    /**
     * 发送位置
     * @param  float|string $lat     纬度
     * @param  float|string $lon     经度
     * @param  string       $title   标题（可选）
     * @param  string       $content 卡片内容（可选）
     * @return string       CQ码
     */
    public static function location($lat, $lon, string $title = '', string $content = ''): string
    {
        $optional_values = [
            'title' => $title ? 'title=' . self::encode($title, true) : '',
            'content' => $content ? 'content=' . self::encode($content, true) : '',
        ];
        return self::buildCQ('location', ['lat' => $lat, 'lon' => $lon], $optional_values);
    }

    /**
     * 发送音乐分享（只能在单条回复中单独使用）
     *
     * qq、163、xiami为内置分享，需要先通过搜索功能获取id后使用
     *
     * @param  string      $type      分享类型（仅限 `qq`、`163`、`xiami` 或 `custom`）
     * @param  int|string  $id_or_url 当分享类型不是 `custom` 时，表示的是分享音乐的ID（需要先通过搜索功能获取id后使用），反之表示的是音乐卡片点入的链接
     * @param  null|string $audio     当分享类型是 `custom` 时，表示为音乐（如mp3文件）的HTTP链接地址（不可为空）
     * @param  null|string $title     当分享类型是 `custom` 时，表示为音乐卡片的标题，建议12字以内（不可为空）
     * @param  null|string $content   当分享类型是 `custom` 时，表示为音乐卡片的简介（可忽略）
     * @param  null|string $image     当分享类型是 `custom` 时，表示为音乐卡片的图片链接地址（可忽略）
     * @return string      CQ码
     */
    public static function music(string $type, $id_or_url, ?string $audio = null, ?string $title = null, ?string $content = null, ?string $image = null): string
    {
        switch ($type) {
            case 'qq':
            case '163':
            case 'xiami':
                return self::buildCQ('music', ['type' => $type, 'id' => $id_or_url]);
            case 'custom':
                if ($title === null || $audio === null) {
                    logger()->warning(zm_internal_errcode('E00035') . '传入CQ码实例的标题和音频链接不能为空！');
                    return ' ';
                }
                $optional_values = [
                    'content' => $content ? 'content=' . self::encode($content, true) : '',
                    'image' => $image ? 'image=' . self::encode($image, true) : '',
                ];
                return self::buildCQ('music', ['type' => 'custom', 'url' => $id_or_url, 'audio' => $audio, 'title' => $title], $optional_values);
            default:
                logger()->warning(zm_internal_errcode('E00035') . "传入的music type({$type})错误！");
                return ' ';
        }
    }

    /**
     * 合并转发消息
     * @param  int|string $id 合并转发ID, 需要通过 `/get_forward_msg` API获取转发的具体内容
     * @return string     CQ码
     */
    public static function forward($id): string
    {
        return self::buildCQ('forward', ['id' => $id]);
    }

    /**
     * 合并转发消息节点
     * 特殊说明: 需要使用单独的API /send_group_forward_msg 发送, 并且由于消息段较为复杂, 仅支持Array形式入参。
     * 如果引用消息和自定义消息同时出现, 实际查看顺序将取消息段顺序。
     * 另外按 CQHTTP 文档说明, data 应全为字符串, 但由于需要接收message 类型的消息, 所以 仅限此Type的content字段 支持Array套娃
     * @param  int|string $user_id  转发消息id
     * @param  string     $nickname 发送者显示名字
     * @param  string     $content  具体消息
     * @return string     CQ码
     * @deprecated 这个不推荐使用，因为 go-cqhttp 官方没有对其提供CQ码模式相关支持，仅支持Array模式发送
     */
    public static function node($user_id, string $nickname, string $content): string
    {
        return self::buildCQ('node', ['user_id' => $user_id, 'nickname' => $nickname, 'content' => $content]);
    }

    /**
     * XML消息
     * @param  string $data xml内容, xml中的value部分
     * @return string CQ码
     */
    public static function xml(string $data): string
    {
        return self::buildCQ('xml', ['data' => $data]);
    }

    /**
     * JSON消息
     * @param  string $data  json内容
     * @param  int    $resid 0为走小程序通道，其他值为富文本通道（默认为0）
     * @return string CQ码
     */
    public static function json(string $data, int $resid = 0): string
    {
        return self::buildCQ('json', ['data' => $data, 'resid' => $resid]);
    }

    /**
     * 返回一个自定义扩展的CQ码（支持自定义类型和参数）
     * @param  string $type_name CQ码类型名称
     * @param  array  $params    参数
     * @return string CQ码
     */
    public static function _custom(string $type_name, array $params): string
    {
        return self::buildCQ($type_name, $params);
    }

    /**
     * 反转义字符串中的CQ码敏感符号
     * @param  int|string|Stringable $msg        字符串
     * @param  bool                  $is_content 如果是解码CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                转义后的CQ码
     */
    public static function decode($msg, bool $is_content = false): string
    {
        $msg = str_replace(['&amp;', '&#91;', '&#93;'], ['&', '[', ']'], (string) $msg);
        if ($is_content) {
            $msg = str_replace('&#44;', ',', $msg);
        }
        return $msg;
    }

    /**
     * 简单反转义替换CQ码的方括号
     * @param  int|string|Stringable $str 字符串
     * @return string                字符串
     */
    public static function replace($str): string
    {
        $str = str_replace('{{', '[', (string) $str);
        return str_replace('}}', ']', $str);
    }

    /**
     * 转义CQ码的特殊字符，同encode
     * @param  int|string|Stringable $msg        字符串
     * @param  bool                  $is_content 如果是转义CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                转义后的CQ码
     */
    public static function escape($msg, bool $is_content = false): string
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], (string) $msg);
        if ($is_content) {
            $msg = str_replace(',', '&#44;', $msg);
        }
        return $msg;
    }

    /**
     * 转义CQ码的特殊字符
     * @param  int|string|Stringable $msg        字符串
     * @param  bool                  $is_content 如果是转义CQ码本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                转义后的CQ码
     */
    public static function encode($msg, bool $is_content = false): string
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], (string) $msg);
        if ($is_content) {
            $msg = str_replace(',', '&#44;', $msg);
        }
        return $msg;
    }

    /**
     * 移除消息中所有的CQ码并返回移除CQ码后的消息
     * @param  string $msg 消息
     * @return string 消息内容
     */
    public static function removeCQ(string $msg): string
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
     * @param  string              $msg       消息内容
     * @param  bool                $is_object 是否以对象形式返回，如果为False的话，返回数组形式（默认为false）
     * @return null|array|CQObject 返回的CQ码（数组或对象）
     */
    public static function getCQ(string $msg, bool $is_object = false)
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
     * @param  string           $msg       消息内容
     * @param  bool             $is_object 是否以对象形式返回，如果为False的话，返回数组形式（默认为false）
     * @return array|CQObject[] 返回的CQ码们（数组或对象）
     */
    public static function getAllCQ(string $msg, bool $is_object = false): array
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

    private static function buildCQ(string $cq, array $array, array $optional_values = []): string
    {
        $str = '[CQ:' . $cq;
        foreach ($array as $k => $v) {
            if ($v === null) {
                logger()->warning('param ' . $k . ' cannot be set with null, empty CQ will returned!');
                return ' ';
            }
            $str .= ',' . $k . '=' . self::encode($v);
        }
        foreach ($optional_values as $v) {
            if ($v !== '') {
                $str .= ',' . $v;
            }
        }
        return $str . ']';
    }
}
