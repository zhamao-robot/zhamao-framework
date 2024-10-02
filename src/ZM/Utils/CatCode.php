<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\V12\Object\MessageSegment;

class CatCode
{
    /**
     * 从 MessageSegment 转换为 CatCode 字符串
     *
     * @param array|MessageSegment|string $message_segment MessageSegment 对象或数组
     * @param bool                        $encode_text     是否对文本进行 CatCode 编码（默认为否）
     */
    public static function fromSegment(array|MessageSegment|string $message_segment, bool $encode_text = false): string
    {
        // 传入的必须是段数组或段对象
        if (is_array($message_segment)) {
            $str = '';
            foreach ($message_segment as $v) {
                if (!$v instanceof MessageSegment) {
                    return '';
                }
                $str .= self::segment2CatCode($v, $encode_text);
            }
            return $str;
        }
        if ($message_segment instanceof MessageSegment) {
            return self::segment2CatCode($message_segment, $encode_text);
        }
        return $message_segment;
    }

    /**
     * 转义CatCode的特殊字符
     *
     * @param  int|string|\Stringable $msg      字符串
     * @param  bool                   $is_param 如果是转义CatCode本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                 转义后的CatCode
     */
    public static function encode(int|string|\Stringable $msg, bool $is_param = false): string
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], (string) $msg);
        if ($is_param) {
            $msg = str_replace([',', '=', "\r", "\n", "\t"], ['&#44;', '&#61;', '&#10;', '&#13;', '&#09;'], $msg);
        }
        return $msg;
    }

    /**
     * 反转义字符串中的CatCode敏感符号
     *
     * @param  int|string|\Stringable $msg      字符串
     * @param  bool                   $is_param 如果是解码CatCode本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                 转义后的CatCode
     */
    public static function decode(int|string|\Stringable $msg, bool $is_param = false): string
    {
        $msg = str_replace(['&amp;', '&#91;', '&#93;'], ['&', '[', ']'], (string) $msg);
        if ($is_param) {
            $msg = str_replace(['&#44;', '&#61;', '&#10;', '&#13;', '&#09;'], [',', '=', "\r", "\n", "\t"], $msg);
        }
        return $msg;
    }

    /**
     * 转换一个 Segment 为 CatCode
     *
     * @param MessageSegment $segment     段对象
     * @param bool           $encode_text 是否对文本进行CatCode转义
     */
    private static function segment2CatCode(MessageSegment $segment, bool $encode_text = false): string
    {
        if (!$encode_text && $segment->type === 'text') {
            return $segment->data['text'];
        }
        $str = '[CatCode:' . $segment->type;
        foreach ($segment->data as $key => $value) {
            $str .= ',' . $key . '=' . self::encode($value, true);
        }
        $str .= ']';
        return $str;
    }
}
