<?php

declare(strict_types=1);

namespace ZM\Middleware;

use OneBot\Util\Singleton;
use ZM\Annotation\AnnotationBase;
use ZM\Exception\InvalidArgumentException;

class MiddlewareHandler
{
    use Singleton;

    /**
     * @var array 存储中间件的
     */
    protected array $middlewares = [];

    /**
     * @var array 存储注册中间件的类和方法
     */
    protected array $reg_map = [];

    /**
     * @var array 用于将中间件名称压栈
     */
    protected array $stack = [];

    /**
     * @var array 用于将正在运行的中间件压栈
     */
    protected array $callable_stack = [];

    public function registerBefore(string $name, callable $callback): void
    {
        $this->middlewares[$name]['before'] = $callback;
    }

    public function registerAfter(string $name, callable $callback): void
    {
        if (
            is_array($callback)                                     // 如果是数组类型callback
            && is_object($callback[0])                              // 且为动态调用
            && isset($this->middlewares[$name]['before'])           // 且存在before
            && is_array($this->middlewares[$name]['before'])        // 且before也是数组类型callback
            && is_object($this->middlewares[$name]['before'][0])    // 且before类型也为动态调用
            && $this->middlewares[$name]['before'][0]::class === $callback[0]::class // 且before和after在一个类
        ) {
            // 那么就把after的对象替换为和before同一个
            $callback[0] = $this->middlewares[$name]['before'][0];
        }
        $this->middlewares[$name]['after'] = $callback;
    }

    public function registerException(string $name, string $exception_class, callable $callback): void
    {
        $this->middlewares[$name]['exception'][$exception_class] = $callback;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function bindMiddleware(callable $callback, string $name, array $args = []): void
    {
        $stack_id = $this->getStackId($callback);
        // TODO: 对中间件是否存在进行检查
        if (class_exists($name)) {
            $obj = resolve($name);
        }

        $this->reg_map[$stack_id][] = [$name, $args];
    }

    public function getPipeClosure(callable $callback, $stack_id, ?AnnotationBase $annotation = null): \Closure
    {
        $pipe_func = function (array $mid_list, ...$args) use ($callback, $stack_id, $annotation, &$pipe_func) {
            $return = true;
            try {
                while (($item = array_shift($mid_list)) !== null) {
                    $this->stack[$stack_id][] = $item;
                    // 如果是 pipeline 形式的中间件，则使用闭包回去
                    if (class_exists($item[0]) && is_a($item[0], PipelineInterface::class, true)) {
                        $resolve = resolve($item[0]);
                        if (method_exists($resolve, 'setAnnotation') && $annotation !== null) {
                            $resolve->setAnnotation($annotation);
                        }
                        if (method_exists($resolve, 'setArgs')) {
                            $resolve->setArgs($item[1]);
                        }
                        return $resolve->handle(function (...$args) use ($mid_list, &$pipe_func) {
                            return $pipe_func($mid_list, ...$args);
                        }, ...$args);
                    } elseif (isset($this->middlewares[$item[0]]['before'])) {
                        $return = container()->call($this->middlewares[$item[0]]['before'], $args);
                        // before 没执行完，直接跳出，不执行本体
                        if ($return === false) {
                            array_pop($this->callable_stack);
                            $mid_list = [];
                            break;
                        }
                    }
                }
                if ($return !== false) {
                    // $args 传递数字索引可能会在部分情况下引发解析错误，应尽量避免
                    // 并尽量避免传递已经绑定入容器的实例
                    // TODO: 可能需要更好的解决方案
                    $result = container()->call($callback, $args);
                }
                while (isset($this->stack[$stack_id]) && ($item = array_pop($this->stack[$stack_id])) !== null) {
                    // 如果是 pipeline 形式的中间件，则使用闭包回去
                    if (class_exists($item[0]) && is_a($item[0], PipelineInterface::class, true)) {
                        continue;
                    }
                    if (isset($this->middlewares[$item[0]]['after'])) {
                        $after_result = container()->call($this->middlewares[$item[0]]['after'], $args);
                    }
                }
            } catch (\Throwable $e) {
                while (isset($this->stack[$stack_id]) && ($item = array_pop($this->stack[$stack_id])) !== null) {
                    // 如果是 pipeline 形式的中间件，则使用闭包回去
                    if (class_exists($item[0]) && is_a($item[0], PipelineInterface::class, true)) {
                        throw $e;
                    }

                    foreach (($this->middlewares[$item[0]]['exception'] ?? []) as $k => $v) {
                        if (is_a($e, $k)) {
                            $exception_result = $v($e);
                            unset($this->stack[$stack_id]);
                            break 2;
                        }
                    }
                }
                if (!isset($exception_result)) {
                    throw $e;
                }
            }
            return $result ?? $after_result ?? $exception_result ?? null;
        };
        unset($this->stack[$stack_id]);
        return $pipe_func;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function process(callable $callback, ...$args)
    {
        $stack_id = $this->getStackId($callback);
        unset($this->stack[$stack_id]);

        $this->callable_stack[] = $callback;

        // 遍历执行before并压栈，并在遇到返回false后停止
        try {
            $mid_list = ($this->reg_map[$stack_id] ?? []);
            $final_result = ($this->getPipeClosure($callback, $stack_id))($mid_list, ...$args);
        } finally {
            array_pop($this->callable_stack);
        }
        return $final_result ?? null;
    }

    public function processWithAnnotation(AnnotationBase $v, callable $callback, ...$args)
    {
        $stack_id = $this->getStackId($callback);
        unset($this->stack[$stack_id]);

        $this->callable_stack[] = $callback;

        // 遍历执行before并压栈，并在遇到返回false后停止
        try {
            $mid_list = ($this->reg_map[$stack_id] ?? []);
            $final_result = ($this->getPipeClosure($callback, $stack_id, $v))($mid_list, ...$args);
        } finally {
            array_pop($this->callable_stack);
        }
        return $final_result ?? null;
    }

    /**
     * 获取正在运行的回调调用对象，可能是Closure、array、string
     *
     * @return false|mixed
     */
    public function getCurrentCallable(): mixed
    {
        return end($this->callable_stack);
    }

    /**
     * @param  callable                 $callback 可执行的方法
     * @throws InvalidArgumentException
     */
    public function getStackId(callable $callback): string
    {
        if ($callback instanceof \Closure) {
            // 闭包情况下，直接根据闭包的ID号来找stack
            return strval(spl_object_id($callback));
        }
        if (is_array($callback) && count($callback) === 2) {
            // 活性调用，根据组合名称来判断
            return (is_object($callback[0]) ? $callback[0]::class : $callback[0]) . '::' . $callback[1];
        }
        if (is_string($callback)) {
            return $callback;
        }
        throw new InvalidArgumentException('传入的 callable 有误！');
    }
}
