<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Koriym\Attributes\AttributeReader;
use Koriym\Attributes\DualReader;
use ZM\Annotation\Http\Controller;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Interfaces\ErgodicAnnotation;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Middleware\Middleware;
use ZM\Store\FileSystem;
use ZM\Utils\HttpUtil;

/**
 * 注解解析器
 */
class AnnotationParser
{
    /**
     * @var array 要解析的路径列表
     */
    private array $path_list = [];

    /**
     * @var float 用于计算解析时间用的
     */
    private float $start_time;

    /**
     * @var array 用于解析的注解解析树，格式见下方的注释
     */
    private array $annotation_tree = [];

    /**
     * @var array 用于生成"类-方法"对应"注解列表"的数组
     */
    private array $annotation_map = [];

    /**
     * @var array 特殊的注解解析器回调列表
     */
    private array $special_parsers = [];

    /**
     * AnnotationParser constructor.
     */
    public function __construct(bool $with_internal_parsers = true)
    {
        $this->start_time = microtime(true);

        // 如果需要，添加内置的注解解析器
        if ($with_internal_parsers) {
            $this->special_parsers = [
                Middleware::class => [function (Middleware $middleware) { \middleware()->bindMiddleware([resolve($middleware->class), $middleware->method], $middleware->name, $middleware->params); }],
                Route::class => [[$this, 'addRouteAnnotation']],
            ];
        }
    }

    /**
     * 设置自定义的注解解析方法
     *
     * @param string   $class_name 注解类名
     * @param callable $callback   回调函数
     */
    public function addSpecialParser(string $class_name, callable $callback)
    {
        $this->special_parsers[$class_name][] = $callback;
    }

    public function parse(array $path): void
    {
        // 写日志
        logger()->debug('parsing annotation in ' . $path[0] . ':' . $path[1]);

        // 首先获取路径下所有的类（通过 PSR-4 标准解析）
        $all_class = FileSystem::getClassesPsr4($path[0], $path[1]);

        // 读取配置文件中配置的忽略解析的注解名，防止误解析一些别的地方需要的注解，比如@mixin
        $conf = config('global.runtime.annotation_reader_ignore');
        // 有两种方式，第一种是通过名称，第二种是通过命名空间
        if (isset($conf['name']) && is_array($conf['name'])) {
            foreach ($conf['name'] as $v) {
                AnnotationReader::addGlobalIgnoredName($v);
            }
        }
        if (isset($conf['namespace']) && is_array($conf['namespace'])) {
            foreach ($conf['namespace'] as $v) {
                AnnotationReader::addGlobalIgnoredNamespace($v);
            }
        }
        // 因为mixin常用，且框架默认不需要解析，则全局忽略
        AnnotationReader::addGlobalIgnoredName('mixin');

        // 声明一个既可以解析注解又可以解析Attribute的双reader来读取注解和Attribute
        $reader = new DualReader(new AnnotationReader(), new AttributeReader());
        foreach ($all_class as $v) {
            logger()->debug('正在检索 ' . $v);

            // 通过反射实现注解读取
            $reflection_class = new \ReflectionClass($v);
            $methods = $reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC);
            $class_annotations = $reader->getClassAnnotations($reflection_class);
            // 这段为新加的:start
            // 这里将每个类里面所有的类注解、方法注解通通加到一颗大树上，后期解析
            /*
            $annotation_map: {
                Module\Example\Hello: {
                    class_annotations: [
                        注解对象1, 注解对象2, ...
                    ],
                    methods: [
                        ReflectionMethod, ReflectionMethod, ...
                    ],
                    methods_annotations: {
                        foo: [ 注解对象1, 注解对象2, ... ],
                        bar: [ 注解对象1, 注解对象2, ... ],
                    }
                }
            }
            */

            // 保存对class的注解
            $this->annotation_tree[$v]['class_annotations'] = $class_annotations;
            // 保存类成员的方法的对应反射对象们
            $this->annotation_tree[$v]['methods'] = $methods;
            // 保存对每个方法获取到的注解们
            foreach ($methods as $method) {
                $this->annotation_tree[$v]['methods_annotations'][$method->getName()] = $reader->getMethodAnnotations($method);
            }

            // 因为适用于类的注解有一些比较特殊，比如有向下注入的，有控制行为的，所以需要遍历一下下放到方法里
            foreach ($this->annotation_tree[$v]['class_annotations'] as $vs) {
                $vs->class = $v;

                // 预处理0：排除所有非继承于 AnnotationBase 的注解
                if (!$vs instanceof AnnotationBase) {
                    logger()->notice(get_class($vs) . ' is not extended from ' . AnnotationBase::class);
                    continue;
                }

                // 预处理1：如果类包含了@Closed注解，则跳过这个类
                if ($vs instanceof Closed) {
                    unset($this->annotation_tree[$v]);
                    continue 2;
                }

                // 预处理2：将适用于每一个函数的注解到类注解重新注解到每个函数下面
                if ($vs instanceof ErgodicAnnotation) {
                    foreach (($this->annotation_tree[$v]['methods'] ?? []) as $method) {
                        // 用 clone 的目的是生成个独立的对象，避免和 class 以及方法之间互相冲突
                        $copy = clone $vs;
                        $copy->method = $method->getName();
                        $this->annotation_tree[$v]['methods_annotations'][$method->getName()][] = $copy;
                    }
                }

                // 预处理3：调用自定义解析器
                foreach (($this->special_parsers[get_class($vs)] ?? []) as $parser) {
                    $result = $parser($vs);
                    if ($result === true) {
                        continue 2;
                    }
                    if ($result === false) {
                        continue 3;
                    }
                }
            }

            // 预处理3：处理每个函数上面的特殊注解，就是需要操作一些东西的
            foreach (($this->annotation_tree[$v]['methods_annotations'] ?? []) as $method_name => $methods_annotations) {
                foreach ($methods_annotations as $method_anno) {
                    // 预处理3.0：排除所有非继承于 AnnotationBase 的注解
                    if (!$method_anno instanceof AnnotationBase) {
                        logger()->notice('Binding annotation ' . get_class($method_anno) . ' to ' . $v . '::' . $method_name . ' is not extended from ' . AnnotationBase::class);
                        continue;
                    }

                    // 预处理3.1：给所有注解对象绑定当前的类名和方法名
                    $method_anno->class = $v;
                    $method_anno->method = $method_name;

                    // 预处理3.2：如果包含了@Closed注解，则跳过这个方法的注解解析
                    if ($method_anno instanceof Closed) {
                        unset($this->annotation_tree[$v]['methods_annotations'][$method_name]);
                        continue 2;
                    }

                    // 预处理3.3：调用自定义解析器
                    foreach (($this->special_parsers[get_class($method_anno)] ?? []) as $parser) {
                        $result = $parser($method_anno);
                        if ($result === true) {
                            continue 2;
                        }
                        if ($result === false) {
                            continue 3;
                        }
                    }

                    // 如果上方没有解析或返回了 true，则添加到注解解析列表中
                    $this->annotation_map[$v][$method_name][] = $method_anno;
                }
            }
        }
    }

    /**
     * 注册各个模块类的注解和模块level的排序
     */
    public function parseAll(): void
    {
        // 对每个设置的路径依次解析
        foreach ($this->path_list as $path) {
            $this->parse($path);
        }
        logger()->debug('解析注解完毕！');
    }

    /**
     * 生成排序后的注解列表
     */
    public function generateAnnotationList(): array
    {
        $o = [];
        foreach ($this->annotation_tree as $obj) {
            // 这里的ErgodicAnnotation是为了解决类上的注解可穿透到方法上的问题
            foreach (($obj['class_annotations'] ?? []) as $class_annotation) {
                if ($class_annotation instanceof ErgodicAnnotation) {
                    continue;
                }
                $o[get_class($class_annotation)][] = $class_annotation;
            }
            foreach (($obj['methods_annotations'] ?? []) as $methods_annotations) {
                foreach ($methods_annotations as $annotation) {
                    $o[get_class($annotation)][] = $annotation;
                }
            }
        }
        foreach ($o as $k => $v) {
            $this->sortByLevel($o, $k);
        }
        return $o;
    }

    /**
     * 添加解析的路径
     *
     * @param string $path        注册解析注解的路径
     * @param string $indoor_name 起始命名空间的名称
     */
    public function addRegisterPath(string $path, string $indoor_name)
    {
        logger()->debug('Add register path: ' . $path . ' => ' . $indoor_name);
        $this->path_list[] = [$path, $indoor_name];
    }

    /**
     * 排序注解列表
     *
     * @param array  $events     需要排序的
     * @param string $class_name 排序的类名
     * @param string $prefix     前缀
     * @internal 用于 level 排序
     */
    public function sortByLevel(array &$events, string $class_name, string $prefix = '')
    {
        if (is_a($class_name, Level::class, true)) {
            $class_name .= $prefix;
            usort($events[$class_name], function ($a, $b) {
                $left = $a->getLevel();
                $right = $b->getLevel();
                return $left > $right ? -1 : ($left == $right ? 0 : 1);
            });
        }
    }

    /**
     * 获取解析器调用的时间（秒）
     */
    public function getUsedTime(): float
    {
        return microtime(true) - $this->start_time;
    }

    /**
     * 获取注解的注册map
     */
    public function getAnnotationMap(): array
    {
        return $this->annotation_map;
    }

    /**
     * 添加注解路由
     */
    private function addRouteAnnotation(Route $vss): void
    {
        // 拿到所属方法的类上面有没有控制器的注解
        $prefix = '';
        foreach (($this->annotation_tree[$vss->class]['methods_annotations'][$vss->method] ?? []) as $annotation) {
            if ($annotation instanceof Controller) {
                $prefix = $annotation->prefix;
                break;
            }
        }
        $tail = trim($vss->route, '/');
        $route_name = $prefix . ($tail === '' ? '' : '/') . $tail;
        logger()->debug('添加路由：' . $route_name);
        $route = new \Symfony\Component\Routing\Route($route_name, ['_class' => $vss->class, '_method' => $vss->method]);
        $route->setMethods($vss->request_method);

        HttpUtil::getRouteCollection()->add(md5($route_name), $route);
    }
}
