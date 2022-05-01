<?php

declare(strict_types=1);

namespace ZM\Utils;

use Exception;
use Iterator;
use ReflectionException;
use ReflectionMethod;
use ZM\Annotation\CQ\CommandArgument;
use ZM\Annotation\CQ\CQCommand;
use ZM\API\CQ;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Entity\InputArguments;
use ZM\Entity\MatchResult;
use ZM\Event\EventManager;
use ZM\Event\EventMapIterator;
use ZM\Exception\WaitTimeoutException;
use ZM\Requests\ZMRequest;
use ZM\Store\WorkerCache;
use ZM\Utils\Manager\WorkerManager;

class MessageUtil
{
    /**
     * 下载消息中 CQ 码的所有图片，通过 url
     * @param  array|string $msg  消息或消息数组
     * @param  null|string  $path 保存路径
     * @return array|false  返回图片信息或失败返回false
     */
    public static function downloadCQImage($msg, ?string $path = null)
    {
        if ($path === null) {
            $path = DataProvider::getDataFolder() . 'images/';
        }
        if (!is_dir($path)) {
            @mkdir($path);
        }
        $path = realpath($path);
        if ($path === false) {
            Console::warning(zm_internal_errcode('E00059') . '指定的路径错误不存在！');
            return false;
        }
        $files = [];
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == 'image') {
                $result = ZMRequest::downloadFile($v->params['url'], $path . '/' . $v->params['file']);
                if ($result === false) {
                    Console::warning(zm_internal_errcode('E00060') . '图片 ' . $v->params['url'] . ' 下载失败！');
                    return false;
                }
                $files[] = $path . '/' . $v->params['file'];
            }
        }
        return $files;
    }

    /**
     * 检查消息中是否含有图片 CQ 码
     * @param array|string $msg 消息或消息数组
     */
    public static function containsImage($msg): bool
    {
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == 'image') {
                return true;
            }
        }
        return false;
    }

    public static function isAtMe($msg, $me_id): bool
    {
        return strpos($msg, CQ::at($me_id)) !== false;
    }

    /**
     * 通过本地地址返回图片的 CQ 码
     * type == 0 : 返回图片的 base64 CQ 码
     * type == 1 : 返回图片的 file://路径 CQ 码（路径必须为绝对路径）
     * type == 2 : 返回图片的 http://xxx CQ 码（默认为 /images/ 路径就是文件对应所在的目录）
     * @param string $file 文件数据
     * @param int    $type 文件类型（0，1，2可选，默认为0）
     */
    public static function getImageCQFromLocal(string $file, int $type = 0): string
    {
        switch ($type) {
            case 0:
                return CQ::image('base64://' . base64_encode(file_get_contents($file)));
            case 1:
                return CQ::image('file://' . $file);
            case 2:
                $info = pathinfo($file);
                return CQ::image(ZMConfig::get('global', 'http_reverse_link') . '/images/' . $info['basename']);
        }
        return '';
    }

    /**
     * 分割字符，将用户消息通过空格或换行分割为数组
     * @param  string         $msg 消息内容
     * @return array|string[]
     */
    public static function splitCommand(string $msg): array
    {
        $word = explode_msg(str_replace("\r", '', $msg));
        if (empty($word)) {
            $word = [''];
        }
        if (count(explode("\n", $word[0])) >= 2) {
            $enter = explode("\n", $msg);
            $first = split_explode(' ', array_shift($enter));
            $word = array_merge($first, $enter);
            foreach ($word as $k => $v) {
                $word[$k] = trim($v);
            }
        }
        return $word;
    }

    /**
     * 根据CQCommand的规则匹配消息，获取是否匹配到对应的注解事件
     * @param array|string   $msg 消息内容
     * @param array|Iterator $obj 数据对象
     */
    public static function matchCommand($msg, $obj): MatchResult
    {
        $ls = EventManager::$events[CQCommand::class] ?? [];
        if (is_array($msg)) {
            $msg = self::arrayToStr($msg);
        }
        $word = self::splitCommand($msg);
        $matched = new MatchResult();
        foreach ($ls as $v) {
            if (array_diff([$v->match, $v->pattern, $v->regex, $v->keyword, $v->end_with, $v->start_with], ['']) == []) {
                continue;
            }
            if (($v->user_id == 0 || ($v->user_id == $obj['user_id']))
                && ($v->group_id == 0 || ($v->group_id == ($obj['group_id'] ?? 0)))
                && ($v->message_type == '' || ($v->message_type == $obj['message_type']))
            ) {
                if (($word[0] != '' && $v->match == $word[0]) || in_array($word[0], $v->alias)) {
                    array_shift($word);
                    $matched->match = $word;
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                }
                if ($v->start_with != '' && mb_substr($msg, 0, mb_strlen($v->start_with)) === $v->start_with) {
                    $matched->match = [mb_substr($msg, mb_strlen($v->start_with))];
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                }
                if ($v->end_with != '' && mb_substr($msg, 0 - mb_strlen($v->end_with)) === $v->end_with) {
                    $matched->match = [substr($msg, 0, strripos($msg, $v->end_with))];
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                }
                if ($v->keyword != '' && mb_strpos($msg, $v->keyword) !== false) {
                    $matched->match = explode($v->keyword, $msg);
                    $matched->object = $v;
                    $matched->status = true;
                    break;
                }
                if ($v->pattern != '') {
                    $match = match_args($v->pattern, $msg);
                    if ($match !== false) {
                        $matched->match = $match;
                        $matched->object = $v;
                        $matched->status = true;
                        break;
                    }
                } elseif ($v->regex != '') {
                    if (preg_match('/' . $v->regex . '/u', $msg, $word2) != 0) {
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

    /**
     * @param  string    $command 命令内容
     * @param  string    $reply   回复内容
     * @throws Exception
     */
    public static function addShortCommand(string $command, string $reply)
    {
        for ($i = 0; $i < ZM_WORKER_NUM; ++$i) {
            WorkerManager::sendActionToWorker($i, 'add_short_command', [$command, $reply]);
        }
    }

    /**
     * 字符串转数组
     * @param  string $msg          消息内容
     * @param  bool   $ignore_space 是否忽略空行
     * @param  bool   $trim_text    是否去除空格
     * @return array  返回数组
     */
    public static function strToArray(string $msg, bool $ignore_space = true, bool $trim_text = false): array
    {
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
            $cq = explode(',', $content);
            $object_type = array_shift($cq);
            $object_params = [];
            foreach ($cq as $v) {
                $key = mb_strstr($v, '=', true);
                $object_params[$key] = CQ::decode(mb_substr(mb_strstr($v, '='), 1), true);
            }
            $arr[] = ['type' => $object_type, 'data' => $object_params];
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
     * @author Copilot
     */
    public static function arrayToStr(array $array): string
    {
        $str = '';
        foreach ($array as $v) {
            if ($v['type'] == 'text') {
                $str .= $v['data']['text'];
            } else {
                $str .= '[CQ:' . $v['type'];
                foreach ($v['data'] as $key => $value) {
                    $str .= ',' . $key . '=' . CQ::encode($value, true);
                }
                $str .= ']';
            }
        }
        return $str;
    }

    /**
     * 根据注解树生成命令列表、帮助
     *
     * @return array 帮助信息，每个元素对应一个命令的帮助信息，格式为：命令名（其他触发条件）：命令描述
     */
    public static function generateCommandHelp(): array
    {
        try {
            if ($cache = WorkerCache::get('command_help')) {
                return $cache;
            }
        } catch (Exception $e) {
            // 不做任何处理，尝试重新生成
        }
        $helps = [];
        foreach (EventManager::$events[CQCommand::class] as $annotation) {
            if ($annotation instanceof CQCommand) {
                try {
                    $reflection = new ReflectionMethod($annotation->class, $annotation->method);
                } catch (ReflectionException $e) {
                    Console::warning('注解解析错误：' . $e->getMessage());
                    continue;
                }
                $doc = $reflection->getDocComment();
                if ($doc) {
                    // 匹配出不以@开头，且后接中文或任意非空格字符，并以换行符结尾的字符串，也就是命令描述
                    preg_match_all('/\*\s((?!@)[\x{4e00}-\x{9fa5}\S]+)(\r\n|\r|\n)/u', $doc, $matches);
                    // 多行描述用分号分隔
                    $help = implode('；', $matches[1]);
                    if (empty($help)) {
                        Console::warning('命令 ' . $annotation->class . '::' . $annotation->method . ' 没有描述！');
                        $help = '无描述';
                    }
                } else {
                    Console::warning('命令 ' . $annotation->class . '::' . $annotation->method . ' 没有描述！');
                    $help = '无描述';
                }

                // 可以触发命令的参数
                $possible_keys = [
                    'match' => '%s',
                    'pattern' => '符合”%s“',
                    'regex' => '匹配“%s”',
                    'start_with' => '以”%s“开头',
                    'end_with' => '以”%s“结尾',
                    'keyword' => '包含“%s”',
                    'alias' => '%s',
                ];
                $command_seg = [];
                foreach ($possible_keys as $key => $help_format) {
                    // 如果定义了该参数，则添加到帮助信息中
                    if (isset($annotation->{$key}) && !empty($annotation->{$key})) {
                        if (is_iterable($annotation->{$key})) {
                            foreach ($annotation->{$key} as $item) {
                                $command_seg[] = sprintf($help_format, $item);
                            }
                        } else {
                            $command_seg[] = sprintf($help_format, $annotation->{$key});
                        }
                    }
                }
                // 第一个触发参数为主命令名
                $command = array_shift($command_seg);
                if (count($command_seg) > 0) {
                    $command .= '（' . implode('，', $command_seg) . '）';
                }
                $helps[] = sprintf('%s：%s', $command, $help);
            }
        }
        // 放到跨进程缓存以供取用
        WorkerCache::set('command_helps', $helps);
        return $helps;
    }

    /**
     * @throws WaitTimeoutException
     */
    public static function checkArguments(string $class, string $method, array &$match): array
    {
        $iterator = new EventMapIterator($class, $method, CommandArgument::class);
        $offset = 0;
        $arguments = [];
        foreach ($iterator as $annotation) {
            /** @var CommandArgument $annotation */
            switch ($annotation->type) {
                case 'string':
                case 'any':
                    if (isset($match[$offset])) {
                        $arguments[$annotation->name] = $match[$offset++];
                    } else {
                        if ($annotation->required) {
                            $value = ctx()->waitMessage($annotation->prompt === '' ? ('请输入' . $annotation->name) : $annotation->prompt, $annotation->timeout);
                            $arguments[$annotation->name] = $value;
                        } else {
                            $arguments[$annotation->name] = $annotation->default;
                        }
                    }
                    break;
                case 'number':
                    for ($k = $offset; $k < count($match); ++$k) {
                        $v = $match[$k];
                        if (is_numeric($v)) {
                            array_splice($match, $k, 1);
                            $arguments[$annotation->name] = $v / 1;
                            break 2;
                        }
                    }
                    if (!$annotation->required) {
                        if (is_numeric($annotation->default)) {
                            $arguments[$annotation->name] = $annotation->default / 1;
                        }
                    }
                    if (!isset($arguments[$annotation->name])) {
                        $value = ctx()->waitMessage($annotation->prompt === '' ? ('请输入' . $annotation->name) : $annotation->prompt, $annotation->timeout);
                        if (!is_numeric($value)) {
                            if ($annotation->error_prompt_policy === 1) {
                                $value = ctx()->waitMessage($annotation->getTypeErrorPrompt(), $annotation->timeout);
                                if (!is_numeric($value)) {
                                    throw new WaitTimeoutException(ctx(), $annotation->getErrorQuitPrompt());
                                }
                            } else {
                                throw new WaitTimeoutException(ctx(), $annotation->getErrorQuitPrompt());
                            }
                        }
                        $arguments[$annotation->name] = $value / 1;
                    }
                    break;
                case 'bool':
                    for ($k = $offset; $k < count($match); ++$k) {
                        $v = strtolower($match[$k]);
                        if (in_array(strtolower($v), TRUE_LIST)) {
                            array_splice($match, $k, 1);
                            $arguments[$annotation->name] = true;
                            break 2;
                        }
                        if (in_array(strtolower($v), FALSE_LIST)) {
                            array_splice($match, $k, 1);
                            $arguments[$annotation->name] = false;
                            break 2;
                        }
                    }
                    if (!$annotation->required) {
                        $default = $annotation->default === '' ? true : 'true';
                        $arguments[$annotation->name] = in_array($default, TRUE_LIST);
                    }
                    if (!isset($arguments[$annotation->name])) {
                        $value = strtolower(ctx()->waitMessage($annotation->prompt === '' ? ('请输入' . $annotation->name) : $annotation->prompt, $annotation->timeout));
                        if (!in_array($value, array_merge(TRUE_LIST, FALSE_LIST))) {
                            if ($annotation->error_prompt_policy === 1) {
                                $value = strtolower(ctx()->waitMessage($annotation->getTypeErrorPrompt(), $annotation->timeout));
                                if (!in_array($value, array_merge(TRUE_LIST, FALSE_LIST))) {
                                    throw new WaitTimeoutException(ctx(), $annotation->getErrorQuitPrompt());
                                }
                            } else {
                                throw new WaitTimeoutException(ctx(), $annotation->getErrorQuitPrompt());
                            }
                        }
                        $arguments[$annotation->name] = in_array($value, TRUE_LIST);
                    }
                    break;
            }
        }
        container()->instance(InputArguments::class, new InputArguments($arguments));
        return $arguments;
    }
}
