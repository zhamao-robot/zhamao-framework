<?php

declare(strict_types=1);

namespace Tests\ZM\Event;

use Tests\TestCase;
use ZM\Annotation\Framework\BindEvent;
use ZM\Event\EventProvider;
use ZM\Utils\ZMUtil;

/**
 * @internal
 */
class EventProviderTest extends TestCase
{
    private EventProvider $ep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ep = new EventProvider();
    }

    public function testAddEventListenerWithCallback(): void
    {
        $event = 'test';
        $callback = fn () => null;
        $this->ep->addEventListener($event, $callback, 2496);
        $l = $this->ep->getEventListeners($event);
        $this->assertSame([
            [2496, $callback],
        ], $l);
    }

    public function testAddEventListenerWithObject(): void
    {
        // no meaning for using ZMUtil, just for testing
        $event = new BindEvent(ZMUtil::class, 2496);
        $event->class = self::class;
        $event->method = 'testAddEventListenerWithObject';
        $callback = fn () => null;
        $this->ep->addEventListener($event, $callback, 2496);
        $l = $this->ep->getEventListeners($event::class);
        $this->assertIsArray($l);
        $this->assertSame(self::class, $l[0][1][0]::class);
        $this->assertSame('testAddEventListenerWithObject', $l[0][1][1]);
    }

    public function testAddEventListenerWithCallableArray(): void
    {
        if (PHP_VERSION_ID >= 80400) {
            $this->markTestSkipped('PHP 8.4.0 has a bug that cannot pass this test');
        }
        // no meaning for using ZMUtil, just for testing
        $event = new ZMUtil();
        $callback = [$this, 'testAddEventListenerWithCallableArray'];
        $this->ep->addEventListener($event, $callback, 2496);
        $l = $this->ep->getEventListeners(ZMUtil::class);
        $this->assertIsArray($l);
        $this->assertSame('testAddEventListenerWithCallableArray', $l[0][1][1]);
    }

    public function testAddEventListenerWithCustomName(): void
    {
        $event = new class() {
            public function getName(): string
            {
                return 'test';
            }
        };
        $callback = fn () => null;
        $this->ep->addEventListener('test', $callback, 2496);
        $l = $this->ep->getEventListeners('test');
        $ll = $this->ep->getListenersForEvent($event);
        $this->assertSame($l, $ll);
    }
}
