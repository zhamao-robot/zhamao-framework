<?php

declare(strict_types=1);

namespace Tests\ZM\Container;

use PHPUnit\Framework\TestCase;
use ZM\Container\Container;
use ZM\Container\WorkerContainer;

/**
 * @internal
 */
class ContainerTest extends TestCase
{
    public function testCanInheritParentBinding(): void
    {
        $worker_container = new WorkerContainer();
        $worker_container->instance('foo', 'bar');

        $container = new Container();
        $container->instance('baz', 'qux');

        // 获取父容器的实例
        $this->assertEquals('bar', $container->get('foo'));

        // 获取自身容器的实例
        $this->assertEquals('qux', $container->get('baz'));
    }

    public function testCanOverrideParentBinding(): void
    {
        $worker_container = new WorkerContainer();
        $worker_container->instance('foo', 'bar');

        $container = new Container();
        $container->instance('foo', 'qux');

        $this->assertEquals('qux', $container->get('foo'));
    }

    public function testCannotModifyParentBinding(): void
    {
        $worker_container = new WorkerContainer();
        $worker_container->instance('foo', 'bar');

        $container = new Container();
        $container->instance('foo', 'qux');

        $this->assertEquals('bar', $worker_container->get('foo'));
    }
}
