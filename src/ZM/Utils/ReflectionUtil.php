<?php

declare(strict_types=1);

namespace ZM\Utils;

use ReflectionNamedType;
use ReflectionParameter;

class ReflectionUtil
{
    /**
     * 获取参数的类名（如有）
     *
     * @param  ReflectionParameter $parameter 参数
     * @return null|string         类名，如果参数不是类，返回 null
     */
    public static function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        // 获取参数类型
        $type = $parameter->getType();
        // 没有声明类型或为基本类型
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        // 获取类名
        $class_name = $type->getName();

        // 如果存在父类
        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($class_name === 'self') {
                return $class->getName();
            }

            if ($class_name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $class_name;
    }
}
