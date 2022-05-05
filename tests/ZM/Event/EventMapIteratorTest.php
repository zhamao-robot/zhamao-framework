<?php

declare(strict_types=1);

namespace Tests\ZM\Event;

use Module\Example\Hello;
use PHPUnit\Framework\TestCase;
use ZM\Annotation\CQ\CommandArgument;
use ZM\Event\EventMapIterator;

/**
 * @internal
 */
class EventMapIteratorTest extends TestCase
{
    public function testIterator(): void
    {
        $iterator = new EventMapIterator(Hello::class, 'randNum', CommandArgument::class);
        $arr = iterator_to_array($iterator);
        $this->assertArrayNotHasKey(0, $arr);
        $this->assertArrayNotHasKey(1, $arr);
        $this->assertArrayHasKey(2, $arr);
        $this->assertArrayHasKey(3, $arr);
        $this->assertInstanceOf(CommandArgument::class, $arr[2]);
        $this->assertInstanceOf(CommandArgument::class, $arr[3]);
        $iterator = new EventMapIterator(Hello::class, 'closeUnknownConn', CommandArgument::class);
        $ls = iterator_to_array($iterator);
        $this->assertCount(0, $ls);
    }
}
