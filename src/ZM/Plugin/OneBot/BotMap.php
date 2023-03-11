<?php

declare(strict_types=1);

namespace ZM\Plugin\OneBot;

use OneBot\V12\Object\OneBotEvent;
use ZM\Context\BotConnectContext;
use ZM\Context\BotContext;
use ZM\Exception\OneBot12Exception;

/**
 * 用于记录多个机器人对应的 fd、flag、状态等的全局关系表（基于反向 WS 类型连接才可用）
 */
class BotMap
{
    /**
     * @internal 仅允许框架内部使用
     * @var array 存储动作 echo 的协程 ID 对应表
     */
    public static array $bot_coroutines = [];

    /**
     * @var array<string, array<string, bool>> 机器人上下文对象列表
     */
    private static array $bot_status = [];

    /**
     * @var array<string, array<string, BotContext>> 机器人上下文缓存对象，避免重复创建
     */
    private static array $bot_ctx_cache = [];

    /**
     * 机器人对应连接 fd
     * 例如：{ "qq": { "123456": [1,2] } }
     *
     * @var array<string, array<string, array>> 机器人对应连接 fd
     */
    private static array $bot_fds = [];

    /**
     * 保存机器人自定义的上下文
     */
    private static array $custom_contexts = [];

    /**
     * 缓存 BotConnectContext 上下文对象的
     *
     * @var array<int, array<int, BotConnectContext>>
     */
    private static array $connect_contexts = [];

    public static function setCustomConnectContext(int $flag, int $fd, BotConnectContext $context): void
    {
        self::$connect_contexts[$flag][$fd] = $context;
    }

    public static function getConnectContext(int $flag, int $fd): BotConnectContext
    {
        return self::$connect_contexts[$flag][$fd] ?? new BotConnectContext($flag, $fd);
    }

    /**
     * 注册机器人
     *
     * @param int|string $bot_id   机器人 ID
     * @param string     $platform 机器人平台
     * @param bool       $status   机器人状态
     * @param int        $fd       绑定的反向 ws 连接的客户端对应 fd
     * @param int        $flag     fd 所在 server 监听端口
     */
    public static function registerBotWithFd(string|int $bot_id, string $platform, bool $status, int $fd, int $flag): bool
    {
        logger()->debug('正在注册机器人：' . "{$platform}:{$bot_id}, fd:{$fd}, flag:{$flag}");
        self::$bot_fds[$platform][strval($bot_id)] = [$flag, $fd];
        self::$bot_status[$platform][strval($bot_id)] = $status;
        return true;
    }

    /**
     * 获取所有机器人对应的 fd
     *
     * @return array<string, array<string, array>>
     */
    public static function getBotFds(): array
    {
        return self::$bot_fds;
    }

    public static function getBotFd(string|int $bot_id, string $platform): ?array
    {
        return self::$bot_fds[$platform][$bot_id] ?? null;
    }

    public static function unregisterBot(string|int $bot_id, string $platform): void
    {
        logger()->debug('取消注册 bot: ' . $bot_id);
        unset(self::$bot_fds[$platform][$bot_id], self::$bot_status[$platform][$bot_id], self::$bot_ctx_cache[$platform][$bot_id]);
    }

    public static function unregisterBotByFd(int $flag, int $fd): void
    {
        // 注销 connect 上下文
        unset(self::$connect_contexts[$flag][$fd]);

        // 注销 bot 上下文
        $unreg_list = [];
        foreach (self::$bot_fds as $platform => $bots) {
            foreach ($bots as $bot_id => $bot_fd) {
                if ($bot_fd[0] === $flag && $bot_fd[1] = $fd) {
                    $unreg_list[] = [$platform, $bot_id];
                }
            }
        }
        foreach ($unreg_list as $item) {
            self::unregisterBot($item[1], $item[0]);
        }
    }

    public static function getBotContext(string|int $bot_id = '', string $platform = ''): BotContext
    {
        if (isset(self::$bot_ctx_cache[$platform][$bot_id])) {
            return self::$bot_ctx_cache[$platform][$bot_id];
        }
        // 如果传入的是空，说明需要通过 cid 来获取事件绑定的机器人，并且机器人没有
        if ($bot_id === '' && $platform === '') {
            if (!container()->has(OneBotEvent::class)) {
                throw new OneBot12Exception('无法在不指定机器人平台、机器人 ID 的情况下在非机器人事件回调内获取机器人上下文');
            }
            $event = container()->get(OneBotEvent::class);
            if (($event->self['platform'] ?? null) === null) {
                throw new OneBot12Exception('无法在不包含机器人 ID 的事件回调内获取机器人上下文');
            }
            // 有，那就通过事件本身的 self 字段来获取一下
            $self = $event->self;
            return self::$bot_ctx_cache[$self['platform']][$self['user_id']] = new (self::$custom_contexts[$self['platform']][$self['user_id']] ?? BotContext::class)($self['user_id'], $self['platform']);
        }
        // 传入的 platform 为空，但 ID 不为空，那么就模糊搜索一个平台的 ID 下的机器人 ID 返回
        if ($platform === '') {
            foreach (self::$bot_fds as $platform => $bot_ids) {
                foreach ($bot_ids as $id => $fd_map) {
                    if ($id === $bot_id) {
                        return self::$bot_ctx_cache[$platform][$id] = new (self::$custom_contexts[$platform][$id] ?? BotContext::class)($id, $platform);
                    }
                }
            }
            throw new OneBot12Exception('未找到 ID 为 ' . $bot_id . ' 的机器人');
        }
        if (!isset(self::$bot_fds[$platform][$bot_id])) {
            throw new OneBot12Exception('未找到 ' . $platform . ' 平台下 ID 为 ' . $bot_id . ' 的机器人');
        }
        return self::$bot_ctx_cache[$platform][$bot_id] = new (self::$custom_contexts[$platform][$bot_id] ?? BotContext::class)($bot_id, $platform);
    }

    public static function setCustomContext(string|int $bot_id, string $platform, string $context_class = BotContext::class): void
    {
        self::$custom_contexts[$platform][$bot_id] = $context_class;
    }
}
