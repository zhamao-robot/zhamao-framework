<?php

declare(strict_types=1);

namespace Tests\ZM\Plugin\OneBot;

use Tests\TestCase;
use ZM\Plugin\OneBot\BotMap;

/**
 * @internal
 */
class BotMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 清理 BotMap 的静态状态，避免用例之间相互污染
        $ref = new \ReflectionClass(BotMap::class);
        foreach (['bot_fds', 'bot_status', 'bot_ctx_cache', 'connect_contexts'] as $prop_name) {
            $prop = $ref->getProperty($prop_name);
            $prop->setValue(null, []);
        }
    }

    public function testUnregisterBotByFdWithSameFlagDifferentFd(): void
    {
        BotMap::registerBotWithFd('10001', 'qq', true, 10, 1);
        BotMap::registerBotWithFd('10002', 'qq', true, 20, 1);

        BotMap::unregisterBotByFd(1, 20);

        // 相同 flag 但 fd 不同的机器人不应被误注销
        $this->assertSame([1, 10], BotMap::getBotFd('10001', 'qq'));
        $this->assertNull(BotMap::getBotFd('10002', 'qq'));
    }

    public function testUnregisterBotByFdWithSameFlagAndFd(): void
    {
        BotMap::registerBotWithFd('10001', 'qq', true, 10, 1);
        BotMap::registerBotWithFd('10002', 'qq', true, 20, 1);

        BotMap::unregisterBotByFd(1, 10);

        // 相同 flag 且相同 fd 的机器人才应被注销
        $this->assertNull(BotMap::getBotFd('10001', 'qq'));
        $this->assertSame([1, 20], BotMap::getBotFd('10002', 'qq'));
    }

    public function testUnregisterBotByFdWithDifferentFlag(): void
    {
        BotMap::registerBotWithFd('10001', 'qq', true, 10, 1);
        BotMap::registerBotWithFd('10002', 'qq', true, 10, 2);

        BotMap::unregisterBotByFd(1, 10);

        // flag 不同但 fd 相同，也不应被误注销
        $this->assertNull(BotMap::getBotFd('10001', 'qq'));
        $this->assertSame([2, 10], BotMap::getBotFd('10002', 'qq'));
    }
}
