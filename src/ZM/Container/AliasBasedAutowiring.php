<?php

declare(strict_types=1);

namespace ZM\Container;

use DI\Definition\Definition;
use DI\Definition\Source\DefinitionSource;

class AliasBasedAutowiring implements DefinitionSource
{
    private DefinitionSource $source;

    public function __construct(DefinitionSource $source)
    {
        $this->source = $source;
    }

    public function getDefinition(string $name): ?Definition
    {
        // 如果是别名，使用类名获取定义
        if (ClassAliasHelper::isAlias($name)) {
            $class = ClassAliasHelper::getAlias($name);
        } else {
            $class = $name;
        }
        return $this->source->getDefinition($class);
    }

    public function getDefinitions(): array
    {
        return $this->source->getDefinitions();
    }
}
