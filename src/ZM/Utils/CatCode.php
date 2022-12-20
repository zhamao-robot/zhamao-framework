<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\V12\Object\MessageSegment;

class CatCode
{
    /**
     * 从 MessageSegment 转换为 CatCode 字符串
     */
    public static function fromSegment(mixed $message_segment): string
    {
        // 传入的必须是段数组或段对象
        if (is_array($message_segment)) {
            $str = '';
            foreach ($message_segment as $v) {
                if (!$v instanceof MessageSegment) {
                    return '';
                }
                $str .= self::segment2CatCode($v);
            }
            return $str;
        }
        if ($message_segment instanceof MessageSegment) {
            return self::segment2CatCode($message_segment);
        }
        if (is_string($message_segment)) {
            return $message_segment;
        }
        return '';
    }

    /**
     * 转义CatCode的特殊字符
     *
     * @param  int|string|\Stringable $msg        字符串
     * @param  bool                   $is_content 如果是转义CatCode本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                 转义后的CatCode
     */
    public static function encode(\Stringable|int|string $msg, bool $is_content = false): string
    {
        $msg = str_replace(['&', '[', ']'], ['&amp;', '&#91;', '&#93;'], (string) $msg);
        if ($is_content) {
            $msg = str_replace(',', '&#44;', $msg);
        }
        return $msg;
    }

    /**
     * 反转义字符串中的CatCode敏感符号
     *
     * @param  int|string|\Stringable $msg        字符串
     * @param  bool                   $is_content 如果是解码CatCode本体内容，则为false（默认），如果是参数内的字符串，则为true
     * @return string                 转义后的CatCode
     */
    public static function decode(\Stringable|int|string $msg, bool $is_content = false): string
    {
        $msg = str_replace(['&amp;', '&#91;', '&#93;'], ['&', '[', ']'], (string) $msg);
        if ($is_content) {
            $msg = str_replace('&#44;', ',', $msg);
        }
        return $msg;
    }

    /**
     * 转换一个 Segment 为 CatCode
     *
     * @param MessageSegment $segment 段对象
     */
    private static function segment2CatCode(MessageSegment $segment): string
    {
        if ($segment->type === 'text') {
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
