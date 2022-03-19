<?php

declare(strict_types=1);

namespace ZM\Utils;

use Closure;
use ZM\Exception\MethodNotFoundException;

trait Macroable
{
    protected static $macros = [];

    public static function __callStatic($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new MethodNotFoundException("Method {$method} does not exist.");
        }
        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(Closure::bind(static::$macros[$method], null, static::class), $parameters);
        }
        return call_user_func_array(static::$macros[$method], $parameters);
    }

    public function __call($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new MethodNotFoundException("Method {$method} does not exist.");
        }
        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
        }
        return call_user_func_array(static::$macros[$method], $parameters);
    }

    public static function macro($name, callable $macro)
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }
}
