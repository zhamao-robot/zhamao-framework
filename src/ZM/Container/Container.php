<?php

declare(strict_types=1);

namespace ZM\Container;

use ReflectionException;

class Container extends WorkerContainer
{
    /**
     * 父容器
     *
     * @var ContainerInterface
     */
    protected $parent;

    /**
     * @param ContainerInterface $parent 父容器
     */
    public function __construct(ContainerInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * 获取父容器
     */
    public function getParent(): ContainerInterface
    {
        return $this->parent;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id identifier of the entry to look for
     */
    public function has(string $id): bool
    {
        return $this->bound($id) || $this->parent->has($id);
    }

    /**
     * 获取一个绑定的实例
     *
     * @param  string                   $abstract   类或接口名
     * @param  array                    $parameters 参数
     * @throws EntryResolutionException
     * @throws ReflectionException
     * @return mixed                    实例
     */
    public function make(string $abstract, array $parameters = [])
    {
        if (isset($this->shared[$abstract])) {
            return $this->shared[$abstract];
        }

        // 此类没有，父类有，则从父类中获取
        if (!$this->bound($abstract) && $this->parent->bound($abstract)) {
            return $this->parent->make($abstract, $parameters);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * 清除所有绑定和实例
     */
    public function flush(): void
    {
        parent::flush();
        $this->parent->flush();
    }
}
