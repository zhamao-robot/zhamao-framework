<?php

declare(strict_types=1);

namespace ZM\Container;

use DI;
use DI\Container;
use DI\ContainerBuilder;

class ContainerHolder
{
    private static ?Container $container = null;

    public static function getEventContainer(): Container
    {
        if (self::$container === null) {
            self::$container = self::buildContainer();
        }
        return self::$container;
    }

    public static function clearEventContainer(): void
    {
        self::$container = null;
    }

    private static function buildContainer(): Container
    {
        $builder = new ContainerBuilder();
        $sources = [
            new DI\Definition\Source\ReflectionBasedAutowiring(),
            new DI\Definition\Source\AttributeBasedAutowiring(),
            new DI\Definition\Source\DefinitionArray(config('container.definitions', [])),
        ];
        $source = new DI\Definition\Source\SourceChain($sources);
        $builder->addDefinitions(new AliasBasedAutowiring($source));
        return $builder->build();
    }
}
