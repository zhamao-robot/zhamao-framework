<?php

declare(strict_types=1);

namespace Tests\ZM\Container;

use PHPUnit\Framework\TestCase;
use ZM\Container\EntryResolutionException;
use ZM\Container\WorkerContainer;
use ZM\Utils\MessageUtil;

/**
 * @internal
 */
class WorkerContainerTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        $this->container = new WorkerContainer();
        $this->container->flush();
    }

    public function testInstance(): void
    {
        $this->container->instance('test', 'test');
        $this->assertEquals('test', $this->container->make('test'));

        $t2 = new WorkerContainer();
        $this->assertEquals('test', $t2->make('test'));
    }

    public function testAlias(): void
    {
        $this->container->alias(MessageUtil::class, 'bar');
        $this->container->alias('bar', 'baz');
        $this->container->alias('baz', 'bas');
        $this->assertInstanceOf(MessageUtil::class, $this->container->make('bas'));
    }

    public function testGetAlias(): void
    {
        $this->container->alias(MessageUtil::class, 'bar');
        $this->assertEquals(MessageUtil::class, $this->container->getAlias('bar'));
    }

    public function testBindClosure(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $this->assertEquals('test', $this->container->make('test'));
    }

    public function testFlush(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $this->container->flush();
        $this->expectException(EntryResolutionException::class);
        $this->container->make('test');
    }

    public function testBindIf(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $this->container->bindIf('test', function () {
            return 'test2';
        });
        $this->assertEquals('test', $this->container->make('test'));
    }

    public function testGet(): void
    {
        $this->testMake();
    }

    public function testBound(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $this->assertTrue($this->container->bound('test'));
        $this->assertFalse($this->container->bound('test2'));
    }

    public function testFactory(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $factory = $this->container->factory('test');
        $this->assertEquals($this->container->make('test'), $factory());
    }

    public function testMake(): void
    {
        $this->container->bind('test', function () {
            return 'test';
        });
        $this->assertEquals('test', $this->container->make('test'));
    }

    public function testHas(): void
    {
        $this->testBound();
    }

    public function testBuild(): void
    {
        $this->assertEquals('test', $this->container->build(function () {
            return 'test';
        }));
        $this->assertInstanceOf(MessageUtil::class, $this->container->build(MessageUtil::class));
    }
}
