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
    public function testInherit(): void
    {
        $worker_container = new WorkerContainer();
        $worker_container->instance('foo', 'bar');

        $container = new Container($worker_container);
        $container->instance('baz', 'qux');

        // 获取父容器的实例
        $this->assertEquals('bar', $container->get('foo'));

        // 获取自身容器的实例
        $this->assertEquals('qux', $container->get('baz'));
    }
}
