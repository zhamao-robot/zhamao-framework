<?php

declare(strict_types=1);

namespace ZM\Container;

use DI\Definition\Definition;
use DI\Definition\Reference;
use DI\Definition\Source\DefinitionSource;

class AliasDefinitionSource implements DefinitionSource
{
    public function getDefinition(string $name): ?Definition
    {
        if (!class_exists($name)) {
            return null;
        }
        $ref = (new \ReflectionClass($name))->getName();
        // 如果反射类名和类名不一致，说明是别名，使用类名获取定义
        return $ref === $name ? null : new Reference($ref);
    }

    public function getDefinitions(): array
    {
        return [];
    }
}
