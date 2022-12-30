<?php

declare(strict_types=1);

namespace ZM\Container;

use DI;
use DI\Container;
use DI\ContainerBuilder;
use OneBot\Driver\Coroutine\Adaptive;

class ContainerHolder
{
    /** @var Container[] */
    private static array $container = [];

    public static function getEventContainer(): Container
    {
        $cid = Adaptive::getCoroutine()?->getCid() ?? -1;
        if (!isset(self::$container[$cid])) {
            self::$container[$cid] = self::buildContainer();
        }
        return self::$container[$cid];
    }

    public static function clearEventContainer(): void
    {
        $cid = Adaptive::getCoroutine()?->getCid() ?? -1;
        unset(self::$container[$cid]);
    }

    private static function buildContainer(): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(
            new AliasDefinitionSource(),
            new DI\Definition\Source\DefinitionArray(config('container.definitions', [])),
        );
        $builder->useAutowiring(true);
        $builder->useAttributes(true);
        return $builder->build();
    }
}
