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

        // 容器缓存
        $enable_cache = config('container.cache.enable', false);
        if (is_callable($enable_cache)) {
            $enable_cache = $enable_cache();
        }
        if ($enable_cache) {
            // 检查 APCu 扩展是否可用
            if (!extension_loaded('apcu')) {
                logger()->warning('APCu 扩展未加载，容器缓存将不可用');
            } else {
                $builder->enableDefinitionCache(config('container.cache.namespace', ''));
            }
        }

        return $builder->build();
    }
}
