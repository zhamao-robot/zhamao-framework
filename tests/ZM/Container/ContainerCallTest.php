<?php

declare(strict_types=1);

namespace Tests\ZM\Container;

use PHPUnit\Framework\TestCase;
use ZM\Container\Container;

/**
 * @internal
 */
class ContainerCallTest extends TestCase
{
    public function testCallInvokableClass(): void
    {
        $container = new Container();
        $this->assertEquals('foo', $container->call(Invokable::class, ['echo' => 'foo']));
    }

    public function testCallClassMethodWithDependencies(): void
    {
        $container = new Container();
        $name = 'Steve' . time();
        $container->bind(FooDependency::class, FooDependencyImpl::class);
        $container->bind(BarDependency::class, function () use ($name) {
            return new BarDependencyImpl($name);
        });
        $this->assertEquals("hello, {$name}", $container->call([Foo::class, 'sayHello']));
    }

    public function testCallClassStaticMethodWithDependencies(): void
    {
        $container = new Container();
        $name = 'Alex' . time();
        $container->bind(FooDependency::class, FooDependencyImpl::class);
        $container->bind(BarDependency::class, function () use ($name) {
            return new BarDependencyImpl($name);
        });
        $this->assertEquals("hello, {$name}", $container->call([Foo::class, 'staticSayHello']));
    }

    public function testCallClassMethodWithDependenciesInjectedByConstructor(): void
    {
        $container = new Container();
        $name = 'Donny' . time();
        $container->bind(FooDependency::class, FooDependencyImpl::class);
        $container->bind(BarDependency::class, function () use ($name) {
            return new BarDependencyImpl($name);
        });
        $this->assertEquals('goodbye', $container->call([Foo::class, 'sayGoodbye']));
    }

    public function testCallClassStaticMethodViaDoubleColons(): void
    {
        $container = new Container();
        $name = 'Herobrine' . time();
        $container->bind(FooDependency::class, FooDependencyImpl::class);
        $container->bind(BarDependency::class, function () use ($name) {
            return new BarDependencyImpl($name);
        });
        $this->assertEquals("hello, {$name}", $container->call(Foo::class . '::staticSayHello'));
    }
}

class Invokable
{
    public function __invoke(string $echo)
    {
        return $echo;
    }
}

interface FooDependency
{
    public function sayGoodbye(): string;
}

class FooDependencyImpl implements FooDependency
{
    public function sayGoodbye(): string
    {
        return 'goodbye';
    }
}

interface BarDependency
{
    public function getName(): string;
}

class BarDependencyImpl implements BarDependency
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class Foo
{
    private $fooDependency;

    public function __construct(FooDependency $fooDependency)
    {
        $this->fooDependency = $fooDependency;
    }

    public function sayHello(BarDependency $barDependency): string
    {
        return 'hello, ' . $barDependency->getName();
    }

    public static function staticSayHello(BarDependency $barDependency): string
    {
        return 'hello, ' . $barDependency->getName();
    }

    public function sayGoodbye(): string
    {
        return $this->fooDependency->sayGoodbye();
    }
}
