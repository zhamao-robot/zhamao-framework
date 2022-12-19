<?php

declare(strict_types=1);

namespace ZM\Utils;

use ReflectionFunction;
use ReflectionMethod;

class ReflectionUtil
{
    /**
     * 获取参数的类名（如有）
     *
     * @param  \ReflectionParameter $parameter 参数
     * @return null|string          类名，如果参数不是类，返回 null
     */
    public static function getParameterClassName(\ReflectionParameter $parameter): ?string
    {
        // 获取参数类型
        $type = $parameter->getType();
        // 没有声明类型或为基本类型
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
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
     */
    public static function variableToString(mixed $var): string
    {
        switch (true) {
            case is_callable($var):
                if (is_array($var)) {
                    if (is_object($var[0])) {
                        return $var[0]::class . '@' . $var[1];
                    }
                    return $var[0] . '::' . $var[1];
                }
                return 'closure';
            case is_string($var):
                return $var;
            case is_array($var):
                return 'array' . json_encode($var, JSON_THROW_ON_ERROR);
            case is_object($var):
                return $var::class;
            case is_resource($var):
                return 'resource(' . get_resource_type($var) . ')';
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

    /**
     * 判断传入的回调是否为任意类的非静态方法
     *
     * @param  callable|string      $callback 回调
     * @throws \ReflectionException
     */
    public static function isNonStaticMethod(callable|string $callback): bool
    {
        if (is_array($callback) && is_string($callback[0])) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
            return !$reflection->isStatic();
        }
        return false;
    }

    /**
     * 获取传入的回调的反射实例
     *
     * 如果传入的是类方法，则会返回 {@link ReflectionMethod} 实例
     * 否则将返回 {@link ReflectionFunction} 实例
     *
     * 可传入实现了 __invoke 的类
     *
     * @param  callable|string      $callback 回调
     * @throws \ReflectionException
     */
    public static function getCallReflector(callable|string $callback): \ReflectionFunctionAbstract
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && !$callback instanceof \Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);
    }

    /**
     * 获取传入的类方法，并确保其可访问
     *
     * 请不要滥用此方法！！！
     *
     * @param  string               $class  类名
     * @param  string               $method 方法名
     * @throws \ReflectionException
     */
    public static function getMethod(string $class, string $method): \ReflectionMethod
    {
        $class = new \ReflectionClass($class);
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }
}
