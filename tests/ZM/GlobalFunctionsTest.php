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
        $this->assertLessThan(0.1, $time);
    }

    /**
     * @dataProvider providerTestMatchPattern
     */
    public function testMatchPattern(string $pattern, string $subject, bool $expected): void
    {
        $this->assertEquals($expected, match_pattern($pattern, $subject));
    }

    public function providerTestMatchPattern(): array
    {
        return [
            'empty' => ['', '', true],
            'empty subject' => ['foo', '', false],
            'empty pattern' => ['', 'foo', false],
            'simple' => ['foo', 'foo', true],
            'simple case insensitive' => ['FOO', 'foo', true],
            'simple case insensitive 2' => ['foo', 'FOO', true],
            'unicode' => ['föö', 'föö', true],
            'chinese' => ['中文', '中文', true],
            'wildcard' => ['foo*', 'foo', true],
            'wildcard 2' => ['foo*', 'foobar', true],
            'wildcard 3' => ['foo*bar', 'foo with bar', true],
            'wildcard 4' => ['foo*bar', 'foo but no bar with it', false],
            'wildcard with chinese' => ['中文*', '中文', true],
            'wildcard with chinese 2' => ['全世界*中国话', '全世界都在说中国话', true],
            'complex' => ['foo*bar*baz', 'foo with bar and baz', true],
            'regex' => ['[a-z]+', 'foo', false], // regex is not supported yet
            'escaped' => ['foo\\*bar', 'foo*bar', true],
        ];
    }

    public function testZmExec(): void
    {
        $this->assertEquals(['code' => 0, 'signal' => 0, 'output' => "foo\n"], zm_exec('echo foo'));
    }

    public function testZmSleep(): void
    {
        $starttime = microtime(true);
        zm_sleep(0.001);
        $this->assertGreaterThanOrEqual(0.001, microtime(true) - $starttime);
    }
}
