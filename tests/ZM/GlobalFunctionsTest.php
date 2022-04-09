<?php

declare(strict_types=1);

namespace Tests\ZM;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class GlobalFunctionsTest extends TestCase
{
    public function testChain(): void
    {
        $mock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['foo', 'bar', 'baz'])
            ->getMock();
        $mock->expects($this->once())
            ->method('foo')
            ->willReturn('foo');
        $mock->expects($this->once())
            ->method('bar')
            ->with('foo')
            ->willReturn('bar');
        $mock->expects($this->once())
            ->method('baz')
            ->with('bar')
            ->willReturn('baz');

        $result = chain($mock)->foo()->bar(CARRY)->baz(CARRY);

        $this->assertEquals('baz', $result);
    }

    public function testStopwatch(): void
    {
        $time = stopwatch(static function () {
            usleep(10000);
        });
        $this->assertEquals(0.01, round($time, 2));
    }
}
