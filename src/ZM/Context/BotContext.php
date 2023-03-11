<?php

declare(strict_types=1);

namespace ZM\Context;

use DI\DependencyException;
use DI\NotFoundException;
use OneBot\Driver\Coroutine\Adaptive;
use OneBot\V12\Object\ActionResponse;
use OneBot\V12\Object\MessageSegment;
use OneBot\V12\Object\OneBotEvent;
use ZM\Context\Trait\BotActionTrait;
use ZM\Exception\OneBot12Exception;
use ZM\Exception\WaitTimeoutException;
use ZM\Plugin\OneBot\OneBot12Adapter;
use ZM\Schedule\Timer;
use ZM\Utils\MessageUtil;

class BotContext implements ContextInterface
{
    use BotActionTrait;

    /** @var array<string, array<string, BotContext>> 记录机器人的上下文列表 */
    protected static array $bots = [];

    /** @var null|string[] 记录当前上下文绑定的机器人 */
    protected ?array $self;

    /** @var array 如果是 BotCommand 匹配的上下文，这里会存放匹配到的参数 */
    protected array $params = [];

    public function __construct(string $bot_id, string $platform)
    {
        $this->self = ['user_id' => $bot_id, 'platform' => $platform];
    }

    /**
     * 获取机器人事件对象
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getEvent(): OneBotEvent
    {
        return container()->get('bot.event');
    }

    /**
     * 快速回复机器人消息文本
     *
     * @param array|MessageSegment|string|\Stringable $message    消息内容、消息段或消息段数组
     * @param int                                     $reply_mode 回复消息模式，默认为空，可选 ZM_REPLY_MENTION（at 用户）、ZM_REPLY_QUOTE（引用消息）
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function reply(\Stringable|MessageSegment|array|string $message, int $reply_mode = ZM_REPLY_NONE): ActionResponse|bool
    {
        if (container()->has('bot.event')) {
            // 这里直接使用当前上下文的事件里面的参数，不再重新挨个获取怎么发消息的参数
            /** @var OneBotEvent $event */
            $event = container()->get('bot.event');

            // reply 的条件是必须 type=message
            if ($event->type !== 'message') {
                throw new OneBot12Exception('bot()->reply() can only be used in message event.');
            }
            $msg = (is_string($message) ? [new MessageSegment('text', ['text' => $message])] : ($message instanceof MessageSegment ? [$message] : $message));
            container()->set('replied', true);
            // 判断规则
            $this->matchReplyMode($reply_mode, $msg, $event);
            return $this->sendMessage($msg, $event->detail_type, $event->jsonSerialize());
        }
        throw new OneBot12Exception('bot()->reply() can only be used in message event.');
    }

    /**
     * 在当前会话等待用户一条消息
     * 如果是私聊，就在对应的机器人私聊环境下等待
     * 如果是单级群组，就在对应的群组下等待当前消息人的消息
     * 如果是多级群组，则等待最小级下当前消息人的消息
     *
     * @param array|MessageSegment|string|\Stringable $prompt         等待前发送的消息文本
     * @param int                                     $timeout        等待超时时间（单位为秒，默认为 600 秒）
     * @param array|MessageSegment|string|\Stringable $timeout_prompt 超时后提示的消息内容
     * @param int                                     $option         prompt 功能的选项参数
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function prompt(string|\Stringable|MessageSegment|array $prompt = '', int $timeout = 600, string|\Stringable|MessageSegment|array $timeout_prompt = '', int $option = ZM_PROMPT_NONE): null|OneBotEvent|array|string
    {
        if (!container()->has('bot.event')) {
            throw new OneBot12Exception('bot()->prompt() can only be used in message event');
        }
        /** @var OneBotEvent $event */
        $event = container()->get('bot.event');
        if ($event->type !== 'message') {
            throw new OneBot12Exception('bot()->prompt() can only be used in message event');
        }
        // 开始等待输入
        logger()->debug('Waiting user for prompt...');
        if ($prompt !== '') {
            $prompt = $this->applyPromptMode($option, $prompt, $event);
            $reply_resp = $this->reply($prompt);
        }
        if (($co = Adaptive::getCoroutine()) === null) {
            throw new OneBot12Exception('Coroutine is not supported yet, prompt() not works');
        }
        $cid = $co->getCid();
        OneBot12Adapter::addContextPrompt($cid, $event);
        $timer_id = zm_timer_after($timeout * 1000, function () use ($cid) {
            if (OneBot12Adapter::isContextPromptExists($cid)) {
                Adaptive::getCoroutine()->resume($cid, '');
            }
        });
        $result = $co->suspend();
        OneBot12Adapter::removeContextPrompt($cid);
        Timer::del($timer_id);
        if ($result === '') {
            throw new WaitTimeoutException(
                $this,
                $timeout_prompt,
                prompt_response: isset($reply_resp) && $reply_resp instanceof ActionResponse ? $reply_resp : null,
                user_event: $event,
                prompt_option: $option
            );
        }
        return $this->applyPromptReturn($result, $option);
    }

    /**
     * 在当前会话等待用户一条消息，且直接返回消息的字符串形式
     * 如果是私聊，就在对应的机器人私聊环境下等待
     * 如果是单级群组，就在对应的群组下等待当前消息人的消息
     * 如果是多级群组，则等待最小级下当前消息人的消息
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function promptString(string|\Stringable|MessageSegment|array $prompt = '', int $timeout = 600, string|\Stringable|MessageSegment|array $timeout_prompt = '', int $option = ZM_PROMPT_NONE): string
    {
        return $this->prompt($prompt, $timeout, $timeout_prompt, $option | ZM_PROMPT_RETURN_STRING);
    }

    /**
     * 返回是否已经调用过回复了
     */
    public function hasReplied(): bool
    {
        return container()->has('replied') && container()->get('replied') === true;
    }

    /**
     * 重置当前上下文为没有被回复过
     */
    public function flushReply(): void
    {
        container()->set('replied', false);
    }

    /**
     * 获取其他机器人的上下文操作对象
     *
     * @param  string            $bot_id   机器人的 self.user_id 对应的 ID
     * @param  string            $platform 机器人的 self.platform 对应的 platform
     * @throws OneBot12Exception
     */
    public function getBot(string $bot_id, string $platform = ''): BotContext
    {
        if (isset(self::$bots[$bot_id])) {
            if ($platform === '') {
                $one = current(self::$bots[$bot_id]);
                if ($one instanceof BotContext) {
                    return $one;
                }
            } elseif (isset(self::$bots[$bot_id][$platform])) {
                return self::$bots[$bot_id][$platform];
            }
        }
        // 到这里说明没找到对应的机器人，抛出异常
        throw new OneBot12Exception('Bot not found.');
    }

    /**
     * 设置该消息下解析出来的参数列表
     *
     * @param array $params 参数列表
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * 获取单个参数值，不存在则返回 null
     *
     * @param int|string $name 参数名称或索引
     */
    public function getParam(string|int $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    public function getParamString(string|int $name): ?string
    {
        return MessageUtil::getAltMessage($this->params[$name] ?? null);
    }

    /**
     * 获取所有参数
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getSelf(): array
    {
        return $this->self;
    }

    /**
     * 匹配更改 reply 的特殊模式
     *
     * @param int         $reply_mode       回复模式
     * @param array       $message_segments 消息段的引用
     * @param OneBotEvent $event            事件对象
     */
    private function matchReplyMode(int $reply_mode, array &$message_segments, OneBotEvent $event)
    {
        if (($reply_mode & ZM_REPLY_QUOTE) === ZM_REPLY_QUOTE) {
            array_unshift($message_segments, new MessageSegment('reply', ['message_id' => $event->getMessageId(), 'user_id' => $event->getUserId()]));
        }
        if (($reply_mode & ZM_REPLY_MENTION) === ZM_REPLY_MENTION) {
            array_unshift($message_segments, new MessageSegment('mention', ['user_id' => $event->getUserId()]));
        }
    }

    /**
     * 匹配更改 prompt reply 的特殊格式
     *
     * @param  int                                     $option prompt 模式
     * @param  array|MessageSegment|string|\Stringable $prompt 消息或消息段
     * @param  OneBotEvent                             $event  事件对象
     * @return array                                   消息段
     */
    private function applyPromptMode(int $option, array|string|\Stringable|MessageSegment $prompt, OneBotEvent $event): array
    {
        // 先格式化消息
        if ($prompt instanceof MessageSegment) {
            $prompt = [$prompt];
        } elseif (is_string($prompt) || $prompt instanceof \Stringable) {
            $prompt = [strval($prompt)];
        }
        // 然后这里只验证 MENTION 和 QUOTE
        if (($option & ZM_PROMPT_MENTION_USER) === ZM_PROMPT_MENTION_USER) {
            $prompt = [new MessageSegment('mention', ['user_id' => $event->getUserId()]), ...$prompt];
        }
        if (($option & ZM_PROMPT_QUOTE_USER) === ZM_PROMPT_QUOTE_USER) {
            $prompt = [new MessageSegment('reply', ['message_id' => $event->getMessageId(), 'user_id' => $event->getUserId()]), ...$prompt];
        }
        return $prompt;
    }

    /**
     * 匹配 prompt 返回的值类型
     *
     * @param  mixed                         $result 结果
     * @param  int                           $option 传入的选项参数
     * @return null|array|OneBotEvent|string 根据不同匹配类型返回不同的东西
     * @throws OneBot12Exception
     */
    private function applyPromptReturn(mixed $result, int $option): null|OneBotEvent|array|string
    {
        // 必须是 OneBotEvent 且是消息类型
        if (!$result instanceof OneBotEvent || $result->type !== 'message') {
            throw new OneBot12Exception('Internal error for resuming prompt: unknown type ' . gettype($result));
        }
        // 更新容器内的事件绑定对象
        if (($option & ZM_PROMPT_UPDATE_EVENT) === ZM_PROMPT_UPDATE_EVENT) {
            container()->set(OneBotEvent::class, $result);
            container()->set('bot.event', $result);
        }
        // 是否为 string 回复
        if (($option & ZM_PROMPT_RETURN_STRING) === ZM_PROMPT_RETURN_STRING) {
            return $result->getMessageString();
        }
        // 是否为 event 回复
        if (($option & ZM_PROMPT_RETURN_EVENT) === ZM_PROMPT_RETURN_EVENT) {
            return $result;
        }
        return $result->getMessage();
    }
}
