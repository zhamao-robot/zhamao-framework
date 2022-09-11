<?php

declare(strict_types=1);

namespace ZM\Container;

use InvalidArgumentException;
use ReflectionException;
use ReflectionParameter;
use ZM\Utils\ReflectionUtil;

class BoundMethod
{
    /**
     * 调用指定闭包、类方法并注入依赖
     *
     * @param  Container                                    $container
     * @param  callable|string                              $callback
     * @return mixed
     * @throws EntryResolutionException|ReflectionException
     * @throws InvalidArgumentException
     */
    public static function call(ContainerInterface $container, $callback, array $parameters = [], string $default_method = null)
    {
        if (is_string($callback) && !$default_method && method_exists($callback, '__invoke')) {
            $default_method = '__invoke';
        }

        if (is_string($callback) && $default_method) {
            $callback = [$callback, $default_method];
        }

        if (ReflectionUtil::isNonStaticMethod($callback)) {
            $callback[0] = $container->make($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback is not callable.');
        }

        return call_user_func_array($callback, self::getMethodDependencies($container, $callback, $parameters));
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param  callable|string     $callback
     * @throws ReflectionException
     */
    protected static function getMethodDependencies(ContainerInterface $container, $callback, array $parameters = []): array
    {
        $dependencies = [];

        foreach (ReflectionUtil::getCallReflector($callback)->getParameters() as $i => $parameter) {
            if (isset($parameters[$i]) && $parameter->hasType() && ($type = $parameter->getType())) {
                if ($type instanceof \ReflectionNamedType && gettype($parameters[$i]) === $type->getName()) {
                    $dependencies[] = $parameters[$i];
                    continue;
                }
            }
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
        }

        return array_merge($dependencies, array_values($parameters));
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @throws EntryResolutionException
     */
    protected static function addDependencyForCallParameter(
        ContainerInterface $container,
        ReflectionParameter $parameter,
        array &$parameters,
        array &$dependencies
    ): void {
        if (array_key_exists($param_name = $parameter->getName(), $parameters)) {
            $dependencies[] = $parameters[$param_name];

            unset($parameters[$param_name]);
        } elseif (!is_null($class_name = ReflectionUtil::getParameterClassName($parameter))) {
            if (array_key_exists($class_name, $parameters)) {
                $dependencies[] = $parameters[$class_name];

                unset($parameters[$class_name]);
            } elseif ($parameter->isVariadic()) {
                $variadic_dependencies = $container->make($class_name);

                $dependencies = array_merge($dependencies, is_array($variadic_dependencies)
                    ? $variadic_dependencies
                    : [$variadic_dependencies]);
            } else {
                $dependencies[] = $container->make($class_name);
            }
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        } elseif (!array_key_exists($param_name, $parameters) && !$parameter->isOptional()) {
            $message = "无法解析类 {$parameter->getDeclaringClass()->getName()} 的依赖 {$parameter}";

            throw new EntryResolutionException($message);
        }
    }
}
