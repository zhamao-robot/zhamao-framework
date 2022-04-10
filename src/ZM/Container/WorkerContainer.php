<?php

declare(strict_types=1);

namespace ZM\Container;

use Closure;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ZM\Console\Console;
use ZM\Utils\ReflectionUtil;
use ZM\Utils\SingletonTrait;

class WorkerContainer implements ContainerInterface
{
    use SingletonTrait;

    /**
     * @var array
     */
    protected $shared = [];

    /**
     * @var array[]
     */
    protected $buildStack = [];

    /**
     * @var array[]
     */
    protected $with = [];

    /**
     * @var array[]
     */
    private static $bindings = [];

    /**
     * @var object[]
     */
    private static $instances = [];

    /**
     * @var string[]
     */
    private static $aliases = [];

    /**
     * @var Closure[][]
     */
    private static $extenders = [];

    /**
     * 判断对应的类或接口是否已经注册
     *
     * @param string $abstract 类或接口名
     */
    public function bound(string $abstract): bool
    {
        return array_key_exists($abstract, self::$bindings)
            || array_key_exists($abstract, self::$instances)
            || array_key_exists($abstract, $this->shared)
            || $this->isAlias($abstract);
    }

    /**
     * 获取类别名（如存在）
     *
     * @param  string $abstract 类或接口名
     * @return string 别名，不存在时返回传入的类或接口名
     */
    public function getAlias(string $abstract): string
    {
        if (!isset(self::$aliases[$abstract])) {
            return $abstract;
        }

        return $this->getAlias(self::$aliases[$abstract]);
    }

    /**
     * 注册一个类别名
     *
     * @param string $abstract 类或接口名
     * @param string $alias    别名
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new InvalidArgumentException("[{$abstract}] is same as [{$alias}]");
        }

        self::$aliases[$alias] = $abstract;

        if ($this->shouldLog()) {
            $this->log("[{$abstract}] is aliased as [{$alias}]");
        }
    }

    /**
     * 注册绑定
     *
     * @param string              $abstract 类或接口名
     * @param null|Closure|string $concrete 返回类实例的闭包，或是类名
     * @param bool                $shared   是否共享
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        // 如果没有提供闭包，则默认为自动解析类名
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $concrete_name = is_string($concrete) ? $concrete : 'Closure';

        // 如果不是闭包，则认为是类名，此时将其包装在一个闭包中，以方便后续处理
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        self::$bindings[$abstract] = compact('concrete', 'shared');

        if ($this->shouldLog()) {
            $this->log("[{$abstract}] is bound to [{$concrete_name}]" . ($shared ? ' (shared)' : ''));
        }
    }

    /**
     * 注册绑定
     *
     * 在已经绑定时不会重复注册
     *
     * @param string              $abstract 类或接口名
     * @param null|Closure|string $concrete 返回类实例的闭包，或是类名
     * @param bool                $shared   是否共享
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * 注册一个单例绑定
     *
     * @param string              $abstract 类或接口名
     * @param null|Closure|string $concrete 返回类实例的闭包，或是类名
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * 注册一个单例绑定
     *
     * 在已经绑定时不会重复注册
     *
     * @param string              $abstract 类或接口名
     * @param null|Closure|string $concrete 返回类实例的闭包，或是类名
     */
    public function singletonIf(string $abstract, $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * 注册一个已有的实例，效果等同于单例绑定
     *
     * @param  string $abstract 类或接口名
     * @param  mixed  $instance 实例
     * @return mixed
     */
    public function instance(string $abstract, $instance)
    {
        if (isset(self::$instances[$abstract])) {
            return self::$instances[$abstract];
        }

        self::$instances[$abstract] = $instance;

        if ($this->shouldLog()) {
            $this->log("[{$abstract}] is bound to [{$instance}] (instance)");
        }

        return $instance;
    }

    /**
     * 获取一个解析对应类实例的闭包
     *
     * @param string $abstract 类或接口名
     */
    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    /**
     * 清除所有绑定和实例
     */
    public function flush(): void
    {
        self::$aliases = [];
        self::$bindings = [];
        self::$instances = [];

        $this->shared = [];
        $this->buildStack = [];
        $this->with = [];
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
        $abstract = $this->getAlias($abstract);

        $needs_contextual_build = !empty($parameters);

        if (isset($this->shared[$abstract])) {
            if ($this->shouldLog()) {
                $this->log(sprintf(
                    '[%s] resolved (shared)%s',
                    $abstract,
                    ($needs_contextual_build ? ' with ' . implode(', ', $parameters) : '')
                ));
            }
            return $this->shared[$abstract];
        }

        // 如果已经存在在实例池中（通常意味着单例绑定），则直接返回该实例
        if (isset(self::$instances[$abstract]) && !$needs_contextual_build) {
            if ($this->shouldLog()) {
                $this->log("[{$abstract}] resolved (instance)");
            }
            return self::$instances[$abstract];
        }

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstract);

        // 构造该类的实例，并递归解析所有依赖
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        // 如果该类存在扩展器（装饰器），则逐个应用到实例
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // 如果该类被注册为单例，则需要将其存放在实例池中，方便后续取用同一实例
        if (!$needs_contextual_build && $this->isShared($abstract)) {
            $this->shared[$abstract] = $object;
            if ($this->shouldLog()) {
                $this->log("[{$abstract}] added to shared pool");
            }
        }

        // 弹出本次构造的覆盖参数
        array_pop($this->with);

        if ($this->shouldLog()) {
            $this->log(sprintf(
                '[%s] resolved%s',
                $abstract,
                ($needs_contextual_build ? ' with ' . implode(', ', $parameters) : '')
            ));
        }

        return $object;
    }

    /**
     * 实例化具体的类实例
     *
     * @param  Closure|string           $concrete 类名或对应的闭包
     * @throws EntryResolutionException
     * @return mixed
     */
    public function build($concrete)
    {
        // 如果传入的是闭包，则直接执行并返回
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflection = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new EntryResolutionException("指定的类 {$concrete} 不存在");
        }

        if (!$reflection->isInstantiable()) {
            $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflection->getConstructor();

        // 如果不存在构造函数，则代表不需要进一步解析，直接实例化即可
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        // 获取所有依赖的实例
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (EntryResolutionException $e) {
            array_pop($this->buildStack);
            throw $e;
        }

        array_pop($this->buildStack);

        return $reflection->newInstanceArgs($instances);
    }

    /**
     * 调用对应的方法，并自动注入依赖
     *
     * @param  callable|string $callback       对应的方法
     * @param  array           $parameters     参数
     * @param  null|string     $default_method 默认方法
     * @return mixed
     */
    public function call($callback, array $parameters = [], string $default_method = null)
    {
        if ($this->shouldLog()) {
            $this->log(sprintf(
                '[%s] called%s%s',
                $this->getCallableName($callback),
                ($default_method ? ' defaulting' . $default_method : ''),
                ($parameters ? ' with ' . implode(', ', $parameters) : '')
            ));
        }
        return BoundMethod::call($this, $callback, $parameters, $default_method);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id identifier of the entry to look for
     *
     * @throws NotFoundExceptionInterface  no entry was found for **this** identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     *
     * @return mixed entry
     */
    public function get(string $id)
    {
        try {
            return $this->make($id);
        } catch (Exception $e) {
            if ($this->has($id)) {
                throw new EntryResolutionException('', 0, $e);
            }

            throw new EntryNotFoundException('', 0, $e);
        }
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
        return $this->bound($id);
    }

    /**
     * 扩展一个类或接口
     *
     * @param string  $abstract 类或接口名
     * @param Closure $closure  扩展闭包
     */
    public function extend(string $abstract, Closure $closure): void
    {
        $abstract = $this->getAlias($abstract);

        // 如果该类已经被解析过，则直接将扩展器应用到该类的实例上
        // 否则，将扩展器存入扩展器池，等待解析
        if (isset(self::$instances[$abstract])) {
            self::$instances[$abstract] = $closure(self::$instances[$abstract], $this);
        } else {
            self::$extenders[$abstract][] = $closure;
        }

        if ($this->shouldLog()) {
            $this->log("[{$abstract}] extended");
        }
    }

    /**
     * 获取对应类型的所有扩展器
     *
     * @param  string    $abstract 类或接口名
     * @return Closure[]
     */
    protected function getExtenders(string $abstract): array
    {
        $abstract = $this->getAlias($abstract);

        return self::$extenders[$abstract] ?? [];
    }

    /**
     * 判断传入的是否为别名
     */
    protected function isAlias(string $name): bool
    {
        return array_key_exists($name, self::$aliases);
    }

    /**
     * 抛弃所有过时的实例和别名
     *
     * @param string $abstract 类或接口名
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset(
            self::$instances[$abstract],
            self::$aliases[$abstract],
            $this->shared[$abstract]
        );
    }

    /**
     * 获取一个解析对应类的闭包
     *
     * @param string $abstract 类或接口名
     * @param string $concrete 实际类名
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return static function ($container, $parameters = []) use ($abstract, $concrete) {
            $method = $abstract === $concrete ? 'build' : 'make';

            return $container->{$method}($concrete, $parameters);
        };
    }

    /**
     * 获取最后一次的覆盖参数
     */
    protected function getLastParameterOverride(): array
    {
        return $this->with[count($this->with) - 1] ?? [];
    }

    /**
     * 抛出实例化异常
     *
     * @throws EntryResolutionException
     */
    protected function notInstantiable(string $concrete, string $reason = ''): void
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);
            $message = "类 {$concrete} 无法实例化，其被 {$previous} 依赖";
        } else {
            $message = "类 {$concrete} 无法实例化";
        }

        throw new EntryResolutionException("{$message}：{$reason}");
    }

    /**
     * 解析依赖
     *
     * @param  ReflectionParameter[]    $dependencies
     * @throws EntryResolutionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // 如果此依赖存在覆盖参数，则使用覆盖参数
            // 否则，将尝试解析参数
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            // 如果存在临时注入的依赖，则使用临时注入的依赖
            if ($this->hasParameterTypeOverride($dependency)) {
                $results[] = $this->getParameterTypeOverride($dependency);
                continue;
            }

            // 如果类名为空，则代表此依赖是基本类型，且无法对其进行依赖解析
            $class_name = ReflectionUtil::getParameterClassName($dependency);
            $results[] = is_null($class_name)
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            if ($this->shouldLog()) {
                if (is_null($class_name)) {
                    if ($dependency->hasType()) {
                        $class_name = $dependency->getType();
                    } else {
                        $class_name = 'Primitive';
                    }
                }
                $this->log("Dependency [{$class_name} {$dependency->name}] resolved");
            }
        }

        return $results;
    }

    /**
     * 判断传入的参数是否存在覆盖参数
     */
    protected function hasParameterOverride(ReflectionParameter $parameter): bool
    {
        return array_key_exists($parameter->name, $this->getLastParameterOverride());
    }

    /**
     * 获取覆盖参数
     *
     * @return mixed
     */
    protected function getParameterOverride(ReflectionParameter $parameter)
    {
        return $this->getLastParameterOverride()[$parameter->name];
    }

    /**
     * 判断传入的参数是否存在临时注入的参数
     */
    protected function hasParameterTypeOverride(ReflectionParameter $parameter): bool
    {
        if (!$parameter->hasType()) {
            return false;
        }

        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return array_key_exists($type->getName(), $this->getLastParameterOverride());
    }

    /**
     * 获取临时注入的参数
     *
     * @return mixed
     */
    protected function getParameterTypeOverride(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            return [];
        }

        return $this->getLastParameterOverride()[$type->getName()];
    }

    /**
     * 解析基本类型
     *
     * @throws EntryResolutionException 如参数不存在默认值，则抛出异常
     * @return mixed                    对应类型的默认值
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new EntryResolutionException("无法解析类 {$parameter->getDeclaringClass()->getName()} 的参数 {$parameter}");
    }

    /**
     * 解析类
     *
     * @throws EntryResolutionException 如果无法解析类，则抛出异常
     * @return mixed
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            // 尝试解析
            return $this->make($parameter->getClass()->name);
        } catch (EntryResolutionException $e) {
            // 如果参数是可选的，则返回默认值
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);
                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                array_pop($this->with);
                return [];
            }

            throw $e;
        }
    }

    /**
     * 获取类名的实际类型
     *
     * @param  string         $abstract 类或接口名
     * @return Closure|string
     */
    protected function getConcrete(string $abstract)
    {
        if (isset(self::$bindings[$abstract])) {
            return self::$bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * 判断传入的实际类型是否可以构造
     *
     * @param mixed  $concrete 实际类型
     * @param string $abstract 类或接口名
     */
    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * 判断传入的类型是否为共享实例
     *
     * @param string $abstract 类或接口名
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]['shared'])
                && $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * 获取回调的名称
     *
     * @param callable|string $callable 回调
     */
    private function getCallableName($callable): string
    {
        $name = is_string($callable) ? $callable : '{closure}';
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                $name = get_class($callable[0]) . '@' . $callable[1];
            } else {
                $name = $callable[0] . '::' . $callable[1];
            }
        }
        return $name;
    }

    /**
     * 判断是否输出日志
     */
    private function shouldLog(): bool
    {
        return Console::getLevel() >= 4;
    }

    /**
     * 记录日志
     */
    private function log(string $message): void
    {
        Console::debug($message);
    }
}
