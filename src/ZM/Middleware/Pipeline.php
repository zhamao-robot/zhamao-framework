<?php

declare(strict_types=1);

namespace ZM\Middleware;

use ZM\Exception\InvalidArgumentException;

/**
 * Pipeline Inspired by Laravel Framework
 */
class Pipeline
{
    private mixed $value;

    private array $middlewares;

    /**
     * 向管道发送数据
     *
     * @param mixed $value 数据
     */
    public function send(mixed $value): Pipeline
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 指定要过的中间件列表
     *
     * @param array $middlewares 要过的中间件（或者叫兼容管道的中间件也行）列表
     */
    public function through(array $middlewares): Pipeline
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * 接下来要调用的内容
     *
     * @param  callable                 $callback 然后调用一个什么东西
     * @return null|mixed               返回调用结果或null
     * @throws InvalidArgumentException
     */
    public function then(callable $callback)
    {
        $stack_id = middleware()->getStackId($callback);

        // 遍历执行before并压栈，并在遇到返回false后停止
        $final_result = (middleware()->getPipeClosure($callback, $stack_id))($this->middlewares, $this->value);

        return $final_result ?? null;
    }
}
