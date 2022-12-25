<?php

declare(strict_types=1);

namespace Tests\ZM\Event\Listener;

use Tests\TestCase;
use Tests\Trait\HasLogger;
use ZM\Event\Listener\SignalListener;

/**
 * @internal
 */
class SignalListenerTest extends TestCase
{
    use HasLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startMockLogger();
    }

    /**
     * @requires extension pcntl
     */
    public function testListenWorkerSignal(): void
    {
        $l = new SignalListener();
        $l->signalWorker();
        // 检查信号处理器是否被设置
        /** @noinspection PhpComposerExtensionStubsInspection */
        $h = pcntl_signal_get_handler(SIGINT);
        $this->assertIsCallable($h);
        $this->assertEquals([$l, 'onWorkerInt'], $h);
    }
}
