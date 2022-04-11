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

    /**
     * 将传入变量转换为字符串
     *
     * @param mixed $var
     */
    public static function variableToString($var): string
    {
        switch (true) {
            case is_callable($var):
                if (is_array($var)) {
                    if (is_object($var[0])) {
                        return get_class($var[0]) . '@' . $var[1];
                    }
                    return $var[0] . '::' . $var[1];
                }
                return 'closure';
            case is_string($var):
                return $var;
            case is_array($var):
                return 'array' . json_encode($var);
            case is_object($var):
                return get_class($var);
            case is_resource($var):
                return 'resource' . get_resource_type($var);
            case is_null($var):
                return 'null';
            case is_bool($var):
                return $var ? 'true' : 'false';
            case is_float($var):
            case is_int($var):
                return (string) $var;
            default:
                return 'unknown';
        }
    }
}
