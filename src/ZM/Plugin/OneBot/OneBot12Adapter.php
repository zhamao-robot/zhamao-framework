<?php

declare(strict_types=1);

namespace ZM\Plugin\OneBot;

use Choir\Http\HttpFactory;
use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Event\StopException;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\V12\Exception\OneBotException;
use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\MessageSegment;
use OneBot\V12\Object\OneBotEvent;
use OneBot\V12\Validator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\OneBot\BotActionResponse;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\BotEvent;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Container\ContainerRegistrant;
use ZM\Context\BotContext;
use ZM\Exception\InterruptException;
use ZM\Exception\OneBot12Exception;
use ZM\Exception\WaitTimeoutException;
use ZM\Plugin\ZMPlugin;
use ZM\Utils\ConnectionUtil;
use ZM\Utils\MessageUtil;

class OneBot12Adapter extends ZMPlugin
{
    /**
     * 缓存待询问参数的队列
     * 0: 代表 OneBotEvent 对象，即用于判断是否为同一会话环境
     * 1: \Generator 生成器，协程，不多讲
     * 2: BotCommand 注解对象
     * 3: match_result（array）匹配到一半的结果
     *
     * @var array<int, array> 队列
     */
    private static array $argument_prompt_queue = [];

    /**
     * @var array<int, OneBotEvent> 队列
     */
    private static array $context_prompt_queue = [];

    public function __construct(string $submodule = 'onebot12', ?AnnotationParser $parser = null)
    {
        switch ($submodule) {
            case 'onebot12':
                // 处理所有 OneBot 12 的反向 WS 握手事件
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleWSReverseOpen']);
                $this->addEvent(WebSocketMessageEvent::class, [$this, 'handleWSReverseMessage']);
                $this->addEvent(WebSocketCloseEvent::class, [$this, 'handleWSReverseClose']);
                // 在 BotEvent 内处理 BotCommand
                $this->addBotEvent(BotEvent::make(type: 'message', level: 15)->on([$this, 'handleBotCommand']));
                // 在 BotEvent 内处理需要等待回复的 CommandArgument
                $this->addBotEvent(BotEvent::make(type: 'message', level: 49)->on([$this, 'handleCommandArgument']));
                $this->addBotEvent(BotEvent::make(type: 'message', level: 50)->on([$this, 'handleContextPrompt']));
                $this->addBotEvent(BotEvent::make(type: 'meta', detail_type: 'status_update', level: 50)->on([$this, 'handleStatusUpdate']));
                // 处理和声明所有 BotCommand 下的 CommandArgument
                $parser->addSpecialParser(BotCommand::class, [$this, 'parseBotCommand']);
                // 不需要给列表写入 CommandArgument
                $parser->addSpecialParser(CommandArgument::class, fn () => true);
                break;
            case 'onebot12-ban-other-ws':
                // 禁止其他类型的 WebSocket 客户端接入
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleUnknownWSReverseInput'], 1);
                break;
        }
    }

    /**
     * @param int         $cid   协程 ID
     * @param OneBotEvent $event 事件对象
     * @internal 只允许内部使用
     */
    public static function addContextPrompt(int $cid, OneBotEvent $event): void
    {
        self::$context_prompt_queue[$cid] = $event;
    }

    /**
     * @param int $cid 协程 ID
     * @internal 只允许内部使用
     */
    public static function removeContextPrompt(int $cid): void
    {
        unset(self::$context_prompt_queue[$cid]);
    }

    /**
     * @param int $cid 协程 ID
     * @internal 只允许内部使用
     */
    public static function isContextPromptExists(int $cid): bool
    {
        return isset(self::$context_prompt_queue[$cid]);
    }

    /**
     * 将 BotCommand 假设含有 CommandArgument 的话，就注册到参数列表中
     *
     * @param BotCommand $command                 命令对象
     * @param null|array $same_method_annotations 同一个方法的所有注解
     */
    public function parseBotCommand(BotCommand $command, ?array $same_method_annotations = null): ?bool
    {
        if ($same_method_annotations === null) {
            return null;
        }
        foreach ($same_method_annotations as $v) {
            if ($v instanceof CommandArgument) {
                $command->withArgumentObject($v);
            }
        }
        return null;
    }

    /**
     * [CALLBACK] 调用 BotCommand 注解的方法
     *
     * @param  BotContext                  $ctx 机器人环境上下文
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     * @throws OneBot12Exception
     */
    public function handleBotCommand(BotContext $ctx)
    {
        if ($ctx->hasReplied()) {
            return;
        }
        // 匹配消息
        $match_result = $this->matchBotCommand($ctx->getEvent());
        if ($match_result === null) {
            return;
        }
        // 匹配成功，检查 CommandArguments
        /** @var BotCommand $command */
        $command = $match_result[0];
        $arguments = $this->matchCommandArguments($match_result[1], $command);
        // 返回的是生成器，说明有需要询问的参数
        if ($arguments instanceof \Generator) {
            /** @var null|CommandArgument $argument */
            $argument = $arguments->current();
            if ($argument === null) {
                // 是 null 表明返回了空生成器，说明参数都已经匹配完毕
                $ctx->setParams($arguments->getReturn());
                $this->callBotCommand($ctx, $command);
                return;
            }
            $message = MessageSegment::text($argument->prompt === '' ? ('请输入' . $argument->name) : $argument->prompt);
            $ctx->reply([$message]);
            // 然后将此事件放入等待队列
            self::$argument_prompt_queue[] = [$ctx->getEvent(), $arguments, $command, $match_result];
            return;
        }
        $ctx->setParams($arguments);
        // 调用方法
        $this->callBotCommand($ctx, $command);
    }

    /**
     * [CALLBACK] 处理 status_update 事件，更新 BotMap
     *
     * @param OneBotEvent $event 机器人事件
     */
    public function handleStatusUpdate(OneBotEvent $event, WebSocketMessageEvent $message_event): void
    {
        $status = $event->get('status');
        $old = BotMap::getBotFds();
        if (($status['good'] ?? false) === true) {
            foreach (($status['bots'] ?? []) as $bot) {
                BotMap::registerBotWithFd(
                    bot_id: $bot['self']['user_id'],
                    platform: $bot['self']['platform'],
                    status: $bot['good'] ?? false,
                    fd: $message_event->getFd(),
                    flag: $message_event->getSocketFlag()
                );
                if (isset($old[$bot['self']['platform']][$bot['self']['user_id']])) {
                    unset($old[$bot['self']['platform']][$bot['self']['user_id']]);
                }
                logger()->error("[{$bot['self']['platform']}.{$bot['self']['user_id']}] 已接入，状态：" . (($bot['good'] ?? false) ? 'OK' : 'Not OK'));
            }
        } else {
            logger()->debug('该实现状态目前不是正常的，不处理 bots 列表');
            $old = [];
        }
        foreach ($old as $platform => $bot_ids) {
            if (empty($bot_ids)) {
                continue;
            }
            foreach ($bot_ids as $id => $flag_fd) {
                logger()->debug("[{$platform}.{$id}] 已断开！");
                BotMap::unregisterBot($id, $platform);
            }
        }
    }

    /**
     * [CALLBACK] 处理需要等待回复的 CommandArgument
     *
     * @param  BotContext                  $ctx 机器人上下文
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws OneBot12Exception
     * @throws \Throwable
     */
    public function handleCommandArgument(BotContext $ctx)
    {
        // 需要先从队列里找到定义当前会话的 prompt
        // 定义一个会话的标准是：事件的 detail_type，user_id，[group_id]，[guild_id，channel_id] 全部相同
        $new_event = $ctx->getEvent();
        foreach (self::$argument_prompt_queue as $k => $v) {
            /** @var OneBotEvent $old_event */
            $old_event = $v[0];
            if ($old_event->detail_type !== $new_event->detail_type) {
                continue;
            }
            $matched = match ($old_event->detail_type) {
                'private' => $new_event->getUserId() === $old_event->getUserId(),
                'group' => $new_event->getGroupId() === $old_event->getGroupId() && $new_event->getUserId() === $old_event->getUserId(),
                'guild' => $new_event->getGuildId() === $old_event->getGuildId() && $new_event->getChannelId() === $old_event->getChannelId() && $new_event->getUserId() === $old_event->getUserId(),
                default => false,
            };
            if (!$matched) {
                continue;
            }
            array_splice(self::$argument_prompt_queue, $k, 1);
            // 找到了，开始处理
            /** @var \Generator $arguments */
            $arguments = $v[1];
            $new_arguments = $arguments->send($new_event->getMessage());
            if ($new_arguments !== null || $arguments->valid()) {
                // 还有需要询问的参数
                /** @var CommandArgument $argument */
                $argument = $arguments->current();
                $message = MessageSegment::text($argument->prompt === '' ? ('请输入' . $argument->name) : $argument->prompt);
                $ctx->reply([$message]);
                // 然后将此事件放入等待队列
                self::$argument_prompt_queue[] = [$ctx->getEvent(), $arguments, $v[2], $v[3]];
            } else {
                // 所有参数都已经获取到了，调用方法
                $ctx->setParams($arguments->getReturn());
                $this->callBotCommand($ctx, $v[2]);
            }
            return;
        }
    }

    /**
     * [CALLBACK] 处理需要等待回复的 bot()->prompt() 会话消息
     *
     * @param  OneBotEvent        $event 当前事件对象
     * @throws InterruptException
     */
    public function handleContextPrompt(OneBotEvent $event)
    {
        // 必须支持协程才能用
        if (($co = Adaptive::getCoroutine()) === null) {
            return;
        }
        // 遍历等待的信息会话列表
        foreach (self::$context_prompt_queue as $cid => $v) {
            // 类型得一样
            if ($v->detail_type !== $event->detail_type) {
                continue;
            }
            $matched = match ($v->detail_type) {
                'private' => $v->getUserId() === $event->getUserId(),
                'group' => $v->getGroupId() === $event->getGroupId() && $v->getUserId() === $event->getUserId(),
                'channel' => $v->getGuildId() === $event->getGuildId() && $v->getChannelId() === $event->getChannelId() && $v->getUserId() === $event->getUserId(),
                default => false,
            };
            if ($matched) {
                $co->resume($cid, $event);
                AnnotationHandler::interrupt();
            }
        }
    }

    /**
     * @throws StopException
     */
    public function handleUnknownWSReverseInput(WebSocketOpenEvent $event)
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        // 如果不是 12 并且在这个最低等级事件之前还没有设置了连接信息的，一律干掉
        if (empty(ConnectionUtil::getConnection($event->getFd())) && $line[0] !== '12') {
            logger()->warning('不允许接入除 OneBot 12 以外的 WebSocket Client');
            $event->withResponse(HttpFactory::createResponse(403, 'Forbidden'));
            $event->stopPropagation();
        }
    }

    /**
     * [CALLBACK] 接入和认证反向 WS 的连接
     *
     * @throws StopException
     * @throws \JsonException
     */
    public function handleWSReverseOpen(WebSocketOpenEvent $event): void
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] === '12') {
            logger()->info('检测到 OneBot 12 反向 WS 连接，正在进行认证...');
            // 是 OneBot 12 标准的，准许接入，进行鉴权
            $request = $event->getRequest();
            $info = ['impl' => $line[1] ?? 'unknown', 'onebot-version' => '12'];
            if (($stored_token = $event->getSocketConfig()['access_token'] ?? '') !== '') {
                // 测试 Header
                $token = $request->getHeaderLine('Authorization');
                if ($token === '') {
                    // 测试 Query
                    $token = $request->getQueryParams()['access_token'] ?? '';
                }
                $token = explode('Bearer ', $token);
                // 动态和静态鉴权
                if ($stored_token instanceof \Closure) {
                    $stored_token = $stored_token($token[1] ?? null);
                } else {
                    $stored_token = !isset($token[1]) || $token[1] !== $stored_token;
                }
                if (!$stored_token) { // 没有 token，鉴权失败
                    logger()->warning('OneBot 12 反向 WS 连接鉴权失败，拒绝接入');
                    $event->withResponse(HttpFactory::createResponse(401, 'Unauthorized'));
                    $event->stopPropagation();
                }
            }
            logger()->info('OneBot 12 反向 WS 连接鉴权成功，接入成功[' . $event->getFd() . ']');
            // 接入 onebots 等实现需要回传 Sec-WebSocket-Protocol 头（这要求 libob 版本必须在 0.5.7 及以上）
            $event->withResponse(HttpFactory::createResponse(101, headers: ['Sec-WebSocket-Protocol' => $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol')]));
        }
        // 设置 OneBot 相关的东西
        ConnectionUtil::setConnection($event->getFd(), $info ?? []);
    }

    /**
     * [CALLBACK] 处理 WebSocket 消息
     *
     * @param  WebSocketMessageEvent $event 事件对象
     * @throws OneBotException
     * @throws \Throwable
     */
    public function handleWSReverseMessage(WebSocketMessageEvent $event): void
    {
        // 忽略非 OneBot 12 的消息
        $impl = ConnectionUtil::getConnection($event->getFd())['impl'] ?? null;
        if ($impl === null) {
            return;
        }

        // 解析 Frame 到 UTF-8 JSON
        $body = $event->getFrame()->getData();
        $body = json_decode($body, true);
        if ($body === null) {
            logger()->warning('收到非 JSON 格式的消息，已忽略');
            return;
        }

        if (isset($body['type'], $body['detail_type'])) {
            // 如果含有 type，detail_type 字段，表明是 event
            try {
                $obj = new OneBotEvent($body);
            } catch (OneBotException) {
                logger()->debug('收到非 OneBot 12 标准的消息，已忽略');
                return;
            }

            // 绑定容器
            ContainerRegistrant::registerOBEventServices($obj);
            if ($obj->getSelf() !== null) {
                $bot_id = $obj->self['user_id'];
                $platform = $obj->self['platform'];
                if (BotMap::getBotFd($bot_id, $platform) === null) {
                    BotMap::registerBotWithFd($bot_id, $platform, true, $event->getFd(), $event->getSocketFlag());
                }
                container()->set(BotContext::class, bot($obj->self['user_id'], $obj->self['platform']));
            }

            // 调用 BotEvent 事件
            $handler = new AnnotationHandler(BotEvent::class);
            $handler->setRuleCallback(function (BotEvent $event) use ($obj) {
                return ($event->type === null || $event->type === $obj->type)
                    && ($event->sub_type === null || $event->sub_type === $obj->sub_type)
                    && ($event->detail_type === null || $event->detail_type === $obj->detail_type);
            });
            try {
                $handler->handleAll();
            } catch (WaitTimeoutException $e) {
                // 这里是处理 prompt() 下超时的情况的
                if ($e->getTimeoutPrompt() === null) {
                    return;
                }
                if (($e->getPromptOption() & ZM_PROMPT_TIMEOUT_MENTION_USER) === ZM_PROMPT_TIMEOUT_MENTION_USER && ($ev = $e->getUserEvent()) !== null) {
                    $prompt = [MessageSegment::mention($ev->getUserId()), ...$e->getTimeoutPrompt()];
                }
                if (($e->getPromptOption() & ZM_PROMPT_TIMEOUT_QUOTE_SELF) === ZM_PROMPT_TIMEOUT_QUOTE_SELF && ($rsp = $e->getPromptResponse()) !== null && ($ev = $e->getUserEvent()) !== null) {
                    $prompt = [MessageSegment::reply($rsp->data['message_id'], $ev->self['user_id']), ...$e->getTimeoutPrompt()];
                } elseif (($e->getPromptOption() & ZM_PROMPT_TIMEOUT_QUOTE_USER) === ZM_PROMPT_TIMEOUT_QUOTE_USER && ($ev = $e->getUserEvent()) !== null) {
                    $prompt = [MessageSegment::reply($ev->getMessageId(), $ev->getUserId()), ...$e->getTimeoutPrompt()];
                }
                bot()->reply($prompt ?? $e->getTimeoutPrompt());
            }
        } elseif (isset($body['status'], $body['retcode'])) {
            // 如果含有 status，retcode 字段，表明是 action 的 response
            $resp = new ActionResponse();
            $resp->retcode = $body['retcode'];
            $resp->status = $body['status'];
            $resp->message = $body['message'] ?? '';
            $resp->data = $body['data'] ?? [];
            $resp->echo = $body['echo'] ?? null;

            ContainerRegistrant::registerOBActionResponseServices($resp);

            // 调用 BotActionResponse 事件
            $handler = new AnnotationHandler(BotActionResponse::class);
            $handler->setRuleCallback(function (BotActionResponse $event) use ($resp) {
                return ($event->retcode === null || $event->retcode === $resp->retcode)
                    && ($event->status === null || $event->status === $resp->status);
            });
            container()->set(ActionResponse::class, $resp);
            $handler->handleAll();

            // 如果有协程，并且该 echo 记录在案的话，就恢复协程
            BotContext::tryResume($resp);
        }
    }

    public function handleWSReverseClose(WebSocketCloseEvent $event)
    {
        // 忽略非 OneBot 12 的消息
        $impl = ConnectionUtil::getConnection($event->getFd())['impl'] ?? null;
        if ($impl === null) {
            return;
        }
        // 在关闭连接的时候
        BotMap::unregisterBotByFd($event->getSocketFlag(), $event->getFd());
    }

    /**
     * 根据事件匹配规则
     *
     * @param OneBotEvent $event 事件对象
     */
    public function matchBotCommand(OneBotEvent $event): ?array
    {
        /** @var BotCommand[] $ls */
        $ls = AnnotationMap::$_list[BotCommand::class] ?? [];
        $msgs = $event->getMessage();
        $head = '';
        $cmd_explode = [];
        $full_str = '';
        foreach ($msgs as $segment) {
            /** @param \MessageSegment $segment */
            if ($segment->type !== 'text') {
                if ($head === '') {
                    continue;
                }
                $cmd_explode[] = $segment;
                continue;
            }
            // 当没识别到命令头的时候，就当作命令头识别
            if ($head === '') {
                $text = $segment->data['text'];
                $full_str .= $text;
                // 切分字符串
                $nlp = MessageUtil::splitMessage(str_replace("\r", '', $text));
                // 啥也没有，分个锤子
                if (empty($nlp)) {
                    continue;
                }
                // 先预留一个给分组而配置的空间
                $cmd_explode = $nlp;
                $head = $nlp[0];
            } else {
                $full_str .= $segment->data['text'];
                // 如果已经识别到了命令头，就当作命令体识别
                $nlp = MessageUtil::splitMessage(str_replace("\r", '', $segment->data['text']));
                if (empty($nlp)) {
                    continue;
                }
                $cmd_explode = array_merge($cmd_explode, $nlp);
            }
            // 先匹配
        }
        if ($head === '') {
            return null;
        }
        // 遍历所有 BotCommand 注解
        foreach ($ls as $v) {
            /** @var BotCommand $v */
            // 测试 deatil_type
            if ($v->detail_type !== '' && $v->detail_type !== $event->detail_type) {
                continue;
            }
            // 测试 prefix
            if ($v->prefix !== '' && mb_strpos($full_str, $v->prefix) !== 0) {
                continue;
            }
            // 测试 match
            if ($v->match !== '' && ($v->prefix . $v->match) === $head) {
                array_shift($cmd_explode);
                return [$v, $cmd_explode, $full_str];
            }
            // 测试 alias
            if ($v->match !== '' && $v->alias !== [] && in_array($head, array_map(fn ($x) => $v->prefix . $x, $v->alias), true)) {
                array_shift($cmd_explode);
                return [$v, $cmd_explode, $full_str];
            }
            // 测试 pattern
            if ($v->pattern !== '' && ($args = match_args($v->pattern, $full_str)) !== false) {
                return [$v, $args, $full_str];
            }
            // 测试 regex
            if ($v->regex !== '' && preg_match('/' . $v->regex . '/u', $full_str, $match) !== 0) {
                array_shift($match);
                return [$v, $match, $full_str];
            }
            // 测试 start_with
            if ($v->start_with !== '' && mb_strpos($full_str, $v->start_with) === 0) {
                return [$v, [mb_substr($full_str, mb_strlen($v->start_with))], $full_str];
            }
            // 测试 end_with
            if ($v->end_with !== '' && mb_substr($full_str, 0 - mb_strlen($v->end_with)) === $v->end_with) {
                return [$v, [mb_substr($full_str, 0, 0 - mb_strlen($v->end_with))], $full_str];
            }
            // 测试 keyword
            if ($v->keyword !== '' && mb_strpos($full_str, $v->keyword) !== false) {
                return [$v, explode($v->keyword, $full_str), $full_str];
            }
        }

        return null;
    }

    /**
     * 根据匹配结果和 CommandArgument 进行匹配
     *
     * @param  array                $match_result 匹配结果的引用
     * @param  BotCommand           $cmd          注解对象
     * @return array|\Generator     返回 array 时为匹配结果，返回 Generator 时为等待结果
     * @throws WaitTimeoutException
     */
    private function matchCommandArguments(array $match_result, BotCommand $cmd): array|\Generator
    {
        $arguments = [];
        /** @var CommandArgument $argument */
        foreach ($cmd->getArguments() as $argument) {
            switch ($argument->type) {
                case 'string':
                case 'any':
                case 'str':
                    $cnt = count($match_result);
                    for ($k = 0; $k < $cnt; ++$k) {
                        $v = $match_result[$k];
                        if (is_string($v)) {
                            array_splice($match_result, $k, 1);
                            $arguments[$argument->name] = $v;
                            break 2;
                        }
                    }
                    if ($argument->required) {
                        // 不够用，且是必需的，就询问用户（这里可能还需要考虑没有协程环境怎么处理）
                        $g = yield $argument;
                        foreach ($g as $v) {
                            if ($v instanceof MessageSegment && $v->type === 'text') {
                                $arguments[$argument->name] = $v->data['text'];
                                break 2;
                            }
                            if (is_string($v)) {
                                $arguments[$argument->name] = $v;
                                break 2;
                            }
                        }
                        if ($argument->error_prompt_policy === 1) {
                            $prompt = $argument->getTypeErrorPrompt() . "\n" . $argument->prompt;
                            $clone_argument = clone $argument;
                            $clone_argument->prompt = $prompt;
                            $g = yield $clone_argument;
                            foreach ($g as $v) {
                                if ($v instanceof MessageSegment && $v->type === 'text') {
                                    $arguments[$argument->name] = $v->data['text'];
                                    break 2;
                                }
                                if (is_string($v)) {
                                    $arguments[$argument->name] = $v;
                                    break 2;
                                }
                            }
                        }
                        throw new WaitTimeoutException($cmd->name, $argument->getErrorQuitPrompt());
                    } else {
                        // 非必需，就使用缺省值
                        $arguments[$argument->name] = $argument->default;
                    }
                    break;
                case 'number':
                    $cnt = count($match_result);
                    // 遍历现存的参数列表，找到第一个数字
                    for ($k = 0; $k < $cnt; ++$k) {
                        $v = $match_result[$k];
                        if (is_numeric($v)) {
                            array_splice($match_result, $k, 1);
                            $arguments[$argument->name] = $v / 1;
                            break 2;
                        }
                    }
                    // 找不到就看看是不是必需的，如果不是必需的，且缺省值是数字，那么就顶上
                    if (!$argument->required && is_numeric($argument->default)) {
                        $arguments[$argument->name] = $argument->default / 1;
                        break;
                    }
                    // 到这里还没找到，就说明需要询问用户了
                    $g = yield $argument;
                    foreach ($g as $v) {
                        if (is_numeric($v)) {
                            $arguments[$argument->name] = $v / 1;
                            break 2;
                        }
                        if ($v instanceof MessageSegment && $v->type === 'text') {
                            if (is_numeric($v->data['text'])) {
                                $arguments[$argument->name] = $v->data['text'] / 1;
                                break 2;
                            }
                        }
                    }
                    if ($argument->error_prompt_policy === 1) {
                        $prompt = $argument->getTypeErrorPrompt() . "\n" . $argument->prompt;
                        $clone_argument = clone $argument;
                        $clone_argument->prompt = $prompt;
                        $g = yield $clone_argument;
                        foreach ($g as $v) {
                            if (is_numeric($v)) {
                                $arguments[$argument->name] = $v / 1;
                                break 2;
                            }
                            if ($v instanceof MessageSegment && $v->type === 'text') {
                                if (is_numeric($v->data['text'])) {
                                    $arguments[$argument->name] = $v->data['text'] / 1;
                                    break 2;
                                }
                            }
                        }
                    }
                    throw new WaitTimeoutException($cmd->name, $argument->getErrorQuitPrompt());
                case 'bool':
                    // 先遍历参数，找到具有布尔值参数的语言
                    $cnt = count($match_result);
                    for ($k = 0; $k < $cnt; ++$k) {
                        $v = $match_result[$k];
                        // 看看有没有true值
                        if (in_array(strtolower($v), TRUE_LIST, true)) {
                            array_splice($match_result, $k, 1);
                            $arguments[$argument->name] = true;
                            break 2;
                        }
                        // 看看有没有false值
                        if (in_array(strtolower($v), FALSE_LIST, true)) {
                            array_splice($match_result, $k, 1);
                            $arguments[$argument->name] = false;
                            break 2;
                        }
                    }
                    // 如果不是必需的，那就使用缺省值
                    if (!$argument->required) {
                        $arguments[$argument->name] = in_array($argument->default === '' ? true : 'true', TRUE_LIST);
                        break;
                    }
                    // 到这里还没找到，就说明需要询问用户了
                    $g = yield $argument;
                    if (in_array(strtolower($g), TRUE_LIST, true)) {
                        $arguments[$argument->name] = true;
                    } elseif (in_array(strtolower($g), FALSE_LIST, true)) {
                        $arguments[$argument->name] = false;
                    } else {
                        if ($argument->error_prompt_policy === 1) {
                            $prompt = $argument->getTypeErrorPrompt() . "\n" . $argument->prompt;
                            $clone_argument = clone $argument;
                            $clone_argument->prompt = $prompt;
                            $g = yield $clone_argument;
                            if (in_array(strtolower($g), TRUE_LIST, true)) {
                                $arguments[$argument->name] = true;
                            } elseif (in_array(strtolower($g), FALSE_LIST, true)) {
                                $arguments[$argument->name] = false;
                            } else {
                                throw new WaitTimeoutException($cmd->name, $argument->getErrorQuitPrompt());
                            }
                        } else {
                            throw new WaitTimeoutException($cmd->name, $argument->getErrorQuitPrompt());
                        }
                    }
                    break;
                default:
                    // 其他类型，处理富文本
                    $msg_type = mb_substr($argument->type, 1);
                    $cnt = count($match_result);
                    for ($k = 0; $k < $cnt; ++$k) {
                        $v = $match_result[$k];
                        if ($v instanceof MessageSegment && $v->type === $msg_type) {
                            array_splice($match_result, $k, 1);
                            $arguments[$argument->name] = $v;
                            break 2;
                        }
                    }
                    // 如果不是必需的，那就使用缺省值
                    if (!$argument->required && is_array($argument->default)) {
                        // 生成一个消息段的段
                        $a = [new MessageSegment($msg_type, $argument->default)];
                        $arguments[$argument->name] = $a;
                        break;
                    }
                    // 到这里还没找到，就说明需要询问用户了
                    $g = yield $argument;
                    foreach ($g as $v) {
                        if ($v instanceof MessageSegment && $v->type === $msg_type) {
                            $arguments[$argument->name] = $v;
                            break 2;
                        }
                    }
                    if ($argument->error_prompt_policy === 1) {
                        $prompt = $argument->getTypeErrorPrompt() . "\n" . $argument->prompt;
                        $clone_argument = clone $argument;
                        $clone_argument->prompt = $prompt;
                        $g = yield $clone_argument;
                        foreach ($g as $v) {
                            if ($v instanceof MessageSegment && $v->type === $msg_type) {
                                $arguments[$argument->name] = $v;
                                break 2;
                            }
                        }
                    }
                    throw new WaitTimeoutException($cmd->name, $argument->getErrorQuitPrompt());
            }
        }
        $arguments['.unnamed'] = $match_result;
        return $arguments;
    }

    /**
     * @throws InterruptException
     * @throws \Throwable
     */
    private function callBotCommand(BotContext $ctx, BotCommand $cmd)
    {
        $handler = new AnnotationHandler(BotCommand::class);
        $handler->setReturnCallback(function ($result) use ($ctx) {
            if (is_string($result) || $result instanceof MessageSegment) {
                $ctx->reply($result);
                return;
            }
            try {
                Validator::validateMessageSegment($result);
                $ctx->reply($result);
            } catch (\Throwable) {
            }
            if ($ctx->hasReplied()) {
                AnnotationHandler::interrupt();
            }
        });
        $handler->handle($cmd);
    }
}
