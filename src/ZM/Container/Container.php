<?php

declare(strict_types=1);

namespace ZM\Container;

use OneBot\Util\Singleton;

class Container implements ContainerInterface
{
    use Singleton;
    use ContainerTrait {
        ContainerTrait::make as protected traitMake;
    }

    /**
     * 获取父容器
     */
    public function getParent(): ContainerInterface
    {
        return WorkerContainer::getInstance();
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
        return $this->bound($id) || $this->getParent()->has($id);
    }

    /**
     * 获取一个绑定的实例
     *
     * @template T
     * @param  class-string<T>          $abstract   类或接口名
     * @param  array                    $parameters 参数
     * @throws EntryResolutionException
     * @return Closure|mixed|T          实例
     */
    public function make(string $abstract, array $parameters = [])
    {
        if (isset($this->shared[$abstract])) {
            return $this->shared[$abstract];
        }

        // 此类没有，父类有，则从父类中获取
        if (!$this->bound($abstract) && $this->getParent()->bound($abstract)) {
            $this->log("{$abstract} is not bound, but in parent container, using parent container");
            return $this->getParent()->make($abstract, $parameters);
        }

        return $this->traitMake($abstract, $parameters);
    }
}
