<?php

declare(strict_types=1);

namespace ZM\Middleware;

use Closure;
use OneBot\Util\Singleton;
use Throwable;
use ZM\Exception\InvalidArgumentException;

class MiddlewareHandler
{
    use Singleton;

    /**
     * @var array 存储中间件的
     */
    protected $middlewares = [];

    /**
     * @var array 存储注册中间件的类和方法
     */
    protected $reg_map = [];

    /**
     * @var array 用于将中间件名称压栈
     */
    protected $stack = [];

    /**
     * @var array 用于将正在运行的中间件压栈
     */
    protected $callable_stack = [];

    public function registerBefore(string $name, callable $callback)
    {
        $this->middlewares[$name]['before'] = $callback;
    }

    public function registerAfter(string $name, callable $callback)
    {
        if (
            is_array($callback)                                     // 如果是数组类型callback
            && is_object($callback[0])                              // 且为动态调用
            && isset($this->middlewares[$name]['before'])           // 且存在before
            && is_array($this->middlewares[$name]['before'])        // 且before也是数组类型callback
            && is_object($this->middlewares[$name]['before'][0])    // 且before类型也为动态调用
            && get_class($this->middlewares[$name]['before'][0]) === get_class($callback[0]) // 且before和after在一个类
        ) {
            // 那么就把after的对象替换为和before同一个
            $callback[0] = $this->middlewares[$name]['before'][0];
        }
        $this->middlewares[$name]['after'] = $callback;
    }

    public function registerException(string $name, string $exception_class, callable $callback)
    {
        $this->middlewares[$name]['exception'][$exception_class] = $callback;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function bindMiddleware(callable $callback, string $name, array $params = [])
    {
        $stack_id = $this->getStackId($callback);
        // TODO: 对中间件是否存在进行检查
        if (class_exists($name)) {
            $obj = resolve($name);
        }

        $this->reg_map[$stack_id][] = [$name, $params];
    }

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function process(callable $callback, array $args)
    {
        try {
            $before_result = MiddlewareHandler::getInstance()->processBefore($callback, $args);
            if ($before_result) {
                $result = container()->call($callback, $args);
            }
            MiddlewareHandler::getInstance()->processAfter($callback, $args);
        } catch (Throwable $e) {
            MiddlewareHandler::getInstance()->processException($callback, $args, $e);
        }
        return $result ?? null;
    }

    /**
     * 调用中间件的前
     *
     * @param  callable                 $callback 必须是数组形式的动态调用
     * @param  array                    $args     参数列表
     * @throws InvalidArgumentException
     */
    public function processBefore(callable $callback, array $args): bool
    {
        // 压栈ID
        $stack_id = $this->getStackId($callback);
        // 清除之前的
        unset($this->stack[$stack_id]);
        $this->callable_stack[] = $callback;
        // 遍历执行before并压栈，并在遇到返回false后停止
        try {
            foreach (($this->reg_map[$stack_id] ?? []) as $item) {
                $this->stack[$stack_id][] = $item;
                if (isset($this->middlewares[$item[0]]['before'])) {
                    $return = container()->call($this->middlewares[$item[0]]['before'], $args);
                    if ($return === false) {
                        array_pop($this->callable_stack);
                        return false;
                    }
                }
            }
        } finally {
            array_pop($this->callable_stack);
        }
        return true;
    }

    /**
     * 获取正在运行的回调调用对象，可能是Closure、array、string
     *
     * @return false|mixed
     */
    public function getCurrentCallable()
    {
        return end($this->callable_stack);
    }

    /**
     * TODO: 调用中间件的后
     *
     * @param  callable                 $callback 必须是数组形式的动态调用
     * @param  array                    $args     参数列表
     * @throws InvalidArgumentException
     */
    public function processAfter(callable $callback, array $args)
    {
        // 压栈ID
        $stack_id = $this->getStackId($callback);
        // 从栈内倒序取出已经执行过的中间件，并执行after
        $this->callable_stack[] = $callback;
        try {
            while (isset($this->stack[$stack_id]) && ($item = array_pop($this->stack[$stack_id])) !== null) {
                if (isset($this->middlewares[$item[0]]['after'])) {
                    container()->call($this->middlewares[$item[0]]['after'], $args);
                }
            }
        } finally {
            array_pop($this->callable_stack);
        }
    }

    /**
     * TODO: 调用中间件的异常捕获处理
     *
     * @param  callable                 $callback 必须是数组形式的动态调用
     * @param  array                    $args     参数列表
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function processException(callable $callback, array $args, Throwable $throwable)
    {
        // 压栈ID
        $stack_id = $this->getStackId($callback);
        // 从栈内倒序取出已经执行过的中间件，并执行after
        while (isset($this->stack[$stack_id]) && ($item = array_pop($this->stack[$stack_id])) !== null) {
            foreach ($this->middlewares[$item[0]]['exception'] as $k => $v) {
                if (is_a($throwable, $k)) {
                    $v($throwable, ...$args);
                    unset($this->stack[$stack_id]);
                    return;
                }
            }
        }
        throw $throwable;
    }

    /**
     * @param  callable                 $callback 可执行的方法
     * @throws InvalidArgumentException
     */
    private function getStackId(callable $callback): string
    {
        if ($callback instanceof Closure) {
            // 闭包情况下，直接根据闭包的ID号来找stack
            return strval(spl_object_id($callback));
        }
        if (is_array($callback) && count($callback) === 2) {
            // 活性调用，根据组合名称来判断
            return (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]) . '::' . $callback[1];
        }
        if (is_string($callback)) {
            return $callback;
        }
        throw new InvalidArgumentException('传入的 callable 有误！');
    }
}
