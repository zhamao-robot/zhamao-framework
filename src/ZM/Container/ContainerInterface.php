<?php

declare(strict_types=1);

namespace ZM\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Interface ContainerInterface
 *
 * 从 Illuminate WorkerContainer 简化而来，兼容 PSR-11
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * 判断对应的类或接口是否已经注册
     *
     * @param string $abstract 类或接口名
     */
    public function bound(string $abstract): bool;

    /**
     * 注册一个类别名
     *
     * @param string $abstract 类或接口名
     * @param string $alias    别名
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * 注册绑定
     *
     * @param string               $abstract 类或接口名
     * @param null|\Closure|string $concrete 返回类实例的闭包，或是类名
     * @param bool                 $shared   是否共享
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;

    /**
     * 注册绑定
     *
     * 在已经绑定时不会重复注册
     *
     * @param string               $abstract 类或接口名
     * @param null|\Closure|string $concrete 返回类实例的闭包，或是类名
     * @param bool                 $shared   是否共享
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void;

    /**
     * 注册一个单例绑定
     *
     * @param string               $abstract 类或接口名
     * @param null|\Closure|string $concrete 返回类实例的闭包，或是类名
     */
    public function singleton(string $abstract, $concrete = null): void;

    /**
     * 注册一个单例绑定
     *
     * 在已经绑定时不会重复注册
     *
     * @param string               $abstract 类或接口名
     * @param null|\Closure|string $concrete 返回类实例的闭包，或是类名
     */
    public function singletonIf(string $abstract, $concrete = null): void;

    /**
     * 注册一个已有的实例，效果等同于单例绑定
     *
     * @param  string $abstract 类或接口名
     * @param  mixed  $instance 实例
     * @return mixed
     */
    public function instance(string $abstract, $instance);

    /**
     * 获取一个解析对应类实例的闭包
     *
     * @param string $abstract 类或接口名
     */
    public function factory(string $abstract): \Closure;

    /**
     * 清除所有绑定和实例
     */
    public function flush(): void;

    /**
     * 获取一个绑定的实例
     *
     * @template T
     * @param  class-string<T>  $abstract   类或接口名
     * @param  array            $parameters 参数
     * @return \Closure|mixed|T 实例
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * 调用对应的方法，并自动注入依赖
     *
     * @param  callable    $callback       对应的方法
     * @param  array       $parameters     参数
     * @param  null|string $default_method 默认方法
     * @return mixed
     */
    public function call(callable $callback, array $parameters = [], string $default_method = null);
}
