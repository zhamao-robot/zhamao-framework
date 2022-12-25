<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\V12\Object\MessageSegment;

/**
 * 机器人消息处理工具类
 */
class MessageUtil
{
    /**
     * 将消息段无损转换为 CatCode 字符串
     *
     * @param array $message_segment 消息段
     * @param bool  $encode_text     是否对文本进行 CatCode 转义
     */
    public static function arrayToStr(array $message_segment, bool $encode_text = false): string
    {
        return CatCode::fromSegment($message_segment, $encode_text);
    }

    /**
     * 将含有 CatCode 字符串的消息文本无损转换为消息段数组
     *
     * @param  string                 $msg          字符串消息（包含 CatCode 的）
     * @param  bool                   $assoc_result 是否返回关联数组形式。当值为 True 时，返回的是数组形式，否则返回 MessageSegment[] 对象列表形式（默认为 False）
     * @param  bool                   $ignore_space 是否忽略空行（默认为 True）
     * @param  bool                   $trim_text    是否去除空格文本（默认为 False）
     * @return array|MessageSegment[]
     */
    public static function strToArray(string $msg, bool $assoc_result = false, bool $ignore_space = true, bool $trim_text = false): array
    {
        $arr = [];
        while (($rear = mb_strstr($msg, '[CatCode:')) !== false && ($end = mb_strstr($rear, ']', true)) !== false) {
            // 把 [CatCode: 前面的文字生成段落
            $front = mb_strstr($msg, '[CatCode:', true);
            // 如果去掉空格都还有文字，或者不去掉空格有字符，且不忽略空格，则生成段落，否则不生成
            if (($trim_front = trim($front)) !== '' || ($front !== '' && !$ignore_space)) {
                $text = CatCode::decode($trim_text ? $trim_front : $front);
                $arr[] = $assoc_result ? ['type' => 'text', 'data' => ['text' => $text]] : new MessageSegment('text', ['text' => $text]);
            }
            // 处理 CatCode
            $content = mb_substr($end, 4);
            $cq = explode(',', $content);
            $object_type = array_shift($cq);
            $object_params = [];
            foreach ($cq as $v) {
                $key = mb_strstr($v, '=', true);
                $object_params[$key] = CatCode::decode(mb_substr(mb_strstr($v, '='), 1), true);
            }
            $arr[] = $assoc_result ? ['type' => $object_type, 'data' => $object_params] : new MessageSegment($object_type, $object_params);
            $msg = mb_substr(mb_strstr($rear, ']'), 1);
        }
        if (($trim_msg = trim($msg)) !== '' || ($msg !== '' && !$ignore_space)) {
            $text = CatCode::decode($trim_text ? $trim_msg : $msg);
            $arr[] = $assoc_result ? ['type' => 'text', 'data' => ['text' => $text]] : new MessageSegment('text', ['text' => $text]);
        }
        return $arr;
    }

    public static function convertToArr(MessageSegment|\Stringable|array|string $message)
    {
        if (is_array($message)) {
            foreach ($message as $k => $v) {
                if (is_string($v)) {
                    $message[$k] = new MessageSegment('text', ['text' => $v]);
                }
            }
            return $message;
        }
        if ($message instanceof MessageSegment) {
            return [$message];
        }
        if ($message instanceof \Stringable) {
            return new MessageSegment('text', ['text' => $message->__toString()]);
        }
        return new MessageSegment('text', ['text' => $message]);
    }

    /**
     * 分割消息字符串
     *
     * @param array $includes 需要进行切割的字符串，默认包含空格及制表符（\t)
     */
    public static function splitMessage(string $msg, array $includes = [' ', "\t"]): array
    {
        $msg = trim($msg);
        foreach ($includes as $v) {
            $msg = str_replace($v, "\n", $msg);
        }
        $msg_seg = explode("\n", $msg);
        $ls = [];
        foreach ($msg_seg as $v) {
            if (empty(trim($v))) {
                continue;
            }
            $ls[] = trim($v);
        }
        return $ls;
    }

    public static function getAltMessage(null|array|string|MessageSegment $message): string
    {
        if ($message === null) {
            return '';
        }
        if (is_string($message)) {
            return $message;
        }
        if ($message instanceof MessageSegment) {
            $message = [$message];
        }
        $message_string = '';
        foreach ($message as $segment) {
            if ($segment->type === 'text') {
                $message_string .= $segment->data['text'];
            } else {
                $message_string .= '[富文本:' . $segment->type . ']';
            }
        }
        return $message_string;
    }
}
