<?php

declare(strict_types=1);

namespace ZM\Context;

use DI\DependencyException;
use DI\NotFoundException;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\V12\Object\MessageSegment;
use OneBot\V12\Object\OneBotEvent;
use ZM\Context\Trait\BotActionTrait;
use ZM\Exception\OneBot12Exception;
use ZM\Utils\MessageUtil;

class BotContext implements ContextInterface
{
    use BotActionTrait;

    /** @var array<string, array<string, BotContext>> 记录机器人的上下文列表 */
    private static array $bots = [];

    /** @var string[] 记录当前上下文绑定的机器人 */
    private array $self;

    /** @var array 如果是 BotCommand 匹配的上下文，这里会存放匹配到的参数 */
    private array $params = [];

    /** @var bool 用于标记当前上下文会话是否已经调用过 reply() 方法 */
    private bool $replied = false;

    public function __construct(string $bot_id, string $platform, null|WebSocketMessageEvent|HttpRequestEvent $event = null)
    {
        $this->self = ['user_id' => $bot_id, 'platform' => $platform];
        self::$bots[$bot_id][$platform] = $this;
        $this->base_event = $event;
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
     */
    public function reply(\Stringable|MessageSegment|array|string $message, int $reply_mode = ZM_REPLY_NONE)
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
            $this->replied = true;
            // 判断规则
            $this->matchReplyMode($reply_mode, $msg, $event);
            return $this->sendMessage($msg, $event->detail_type, $event->jsonSerialize());
        }
        throw new OneBot12Exception('bot()->reply() can only be used in message event.');
    }

    /**
     * 返回是否已经调用过回复了
     */
    public function hasReplied(): bool
    {
        return $this->replied;
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
}
