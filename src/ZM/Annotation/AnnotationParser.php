<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Koriym\Attributes\AttributeReader;
use Koriym\Attributes\DualReader;
use ZM\Annotation\Framework\Cron;
use ZM\Annotation\Framework\Tick;
use ZM\Annotation\Http\Controller;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Interfaces\ErgodicAnnotation;
use ZM\Annotation\Middleware\Middleware;
use ZM\Schedule\Schedule;
use ZM\Schedule\Timer;
use ZM\Store\FileSystem;
use ZM\Utils\HttpUtil;

/**
 * 注解解析器
 */
class AnnotationParser
{
    /**
     * @var array 要解析的 PSR-4 class 列表
     */
    private array $class_list = [];

    private array $class_bind_group_list = [];

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
                Middleware::class => [function (Middleware $middleware) { \middleware()->bindMiddleware([resolve($middleware->class), $middleware->method], $middleware->name, $middleware->args); }],
                Route::class => [[$this, 'addRouteAnnotation']],
                Closed::class => [fn () => false],
                Cron::class => [[resolve(Schedule::class), 'addSchedule']],
                Tick::class => [[Timer::class, 'registerTick']],
            ];
        }
    }

    /**
     * 设置自定义的注解解析方法
     *
     * @param string   $class_name 注解类名
     * @param callable $callback   回调函数
     */
    public function addSpecialParser(string $class_name, callable $callback): void
    {
        $this->special_parsers[$class_name][] = $callback;
    }

    /**
     * 解析所有传入的 PSR-4 下识别出来的类及下方的注解
     * 返回一个包含三个元素的数组，分别是list、map、tree
     * 其中list为注解列表，key是注解的class名称，value是所有此注解的列表，即[Annotation1, ...]
     * map是类、方法映射表关系的三维数组，即[类名 => [方法名 => [注解1, ...]]]
     * tree是解析中间生成的树结构，内含反射对象，见下方注释
     *
     * @return array[]
     * @throws \ReflectionException
     */
    public function parse(): array
    {
        $reflection_tree = [];
        $annotation_map = [];
        $annotation_list = [];

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
        foreach ($this->class_list as $v) {
            logger()->debug('正在检索 ' . $v);

            // 通过反射实现注解读取
            $reflection_class = new \ReflectionClass($v);
            $methods = $reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC);
            $class_annotations = $reader->getClassAnnotations($reflection_class);
            // 这段为新加的:start
            // 这里将每个类里面所有的类注解、方法注解通通加到一颗大树上，后期解析
            /*
            $reflection_tree: {
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
            // 保留ergodic注解
            $append_ergodics = [];
            // 保存对class的注解
            $reflection_tree[$v]['class_annotations'] = $class_annotations;
            // 保存类成员的方法的对应反射对象们
            $reflection_tree[$v]['methods'] = $methods;
            // 保存对每个方法获取到的注解们
            foreach ($methods as $method) {
                $reflection_tree[$v]['methods_annotations'][$method->getName()] = $reader->getMethodAnnotations($method);
            }

            // 因为适用于类的注解有一些比较特殊，比如有向下注入的，有控制行为的，所以需要遍历一下下放到方法里
            foreach ($reflection_tree[$v]['class_annotations'] as $vs) {
                $vs->class = $v;

                if (in_array($vs::class, $conf['name'])) {
                    continue;
                }
                // 预处理0：排除所有非继承于 AnnotationBase 的注解
                if (!$vs instanceof AnnotationBase) {
                    logger()->notice($vs::class . ' is not extended from ' . AnnotationBase::class);
                    continue;
                }

                // 预处理2：将适用于每一个函数的注解到类注解重新注解到每个函数下面
                if ($vs instanceof ErgodicAnnotation) {
                    foreach ($reflection_tree[$v]['methods'] as $method) {
                        // 用 clone 的目的是生成个独立的对象，避免和 class 以及方法之间互相冲突
                        $copy = clone $vs;
                        $copy->method = $method->getName();
                        $append_ergodics[$method->getName()][] = $copy;
                        // $reflection_tree[$v]['methods_annotations'][$method->getName()][] = $copy;
                        $annotation_list[get_class($vs)][] = $copy;
                    }
                    // 标记为 Ergodic 的类注解，不作为类的注解解析，而是全部当作每个方法有注解，所以直接跳过
                    continue;
                }

                // 预处理3：调用自定义解析器
                if (($a = $this->parseSpecial($vs, $reflection_tree[$v]['class_annotations'])) === true) {
                    continue;
                }
                if ($a === false) {
                    unset($reflection_tree[$v]);
                    continue 2;
                }
                $annotation_list[get_class($vs)][] = $vs;

                // 预处理4：加入组
                if (isset($this->class_bind_group_list[get_class($vs)])) {
                    $vs->group = array_merge($vs->group, $this->class_bind_group_list[get_class($vs)]);
                }
            }
            // 预处理：将Class的ergodic注解拼接到每个方法的注解列表前面，且按照顺序（修复 #365）
            foreach (($reflection_tree[$v]['methods_annotations'] ?? []) as $method_name => $annos) {
                if (isset($append_ergodics[$method_name])) {
                    $reflection_tree[$v]['methods_annotations'][$method_name] = array_merge($append_ergodics[$method_name], $annos);
                }
            }

            // 预处理3：处理每个函数上面的特殊注解，就是需要操作一些东西的
            foreach (($reflection_tree[$v]['methods_annotations'] ?? []) as $method_name => $methods_annotations) {
                foreach ($methods_annotations as $method_anno) {
                    if (in_array($method_anno::class, $conf['name'])) {
                        continue;
                    }
                    // 预处理3.0：排除所有非继承于 AnnotationBase 的注解
                    if (!$method_anno instanceof AnnotationBase) {
                        logger()->notice('Binding annotation ' . $method_anno::class . ' to ' . $v . '::' . $method_name . ' is not extended from ' . AnnotationBase::class);
                        continue;
                    }

                    // 预处理3.1：给所有注解对象绑定当前的类名和方法名
                    $method_anno->class = $v;
                    $method_anno->method = $method_name;

                    // 预处理3.3：调用自定义解析器
                    $a = $this->parseSpecial($method_anno, $methods_annotations);

                    if ($a === true) {
                        continue;
                    }
                    if ($a === false) {
                        unset($reflection_tree[$v]['methods_annotations'][$method_name]);
                        continue 2;
                    }
                    // 如果上方没有解析或返回了 true，则添加到注解解析列表中
                    $annotation_map[$v][$method_name][] = $method_anno;
                    $annotation_list[get_class($method_anno)][] = $method_anno;
                }
            }
        }
        logger()->debug('解析注解完毕！');
        // ob_dump($annotation_list);
        return [$annotation_list, $annotation_map, $reflection_tree];
    }

    /**
     * 生成排序后的注解列表
     */
    public function generateAnnotationListFromMap(): array
    {
        $o = [];
        foreach ($this->annotation_tree as $obj) {
            // 这里的ErgodicAnnotation是为了解决类上的注解可穿透到方法上的问题
            foreach (($obj['class_annotations'] ?? []) as $class_annotation) {
                if ($class_annotation instanceof ErgodicAnnotation) {
                    continue;
                }
                $o[$class_annotation::class][] = $class_annotation;
            }
            foreach (($obj['methods_annotations'] ?? []) as $methods_annotations) {
                foreach ($methods_annotations as $annotation) {
                    $o[$annotation::class][] = $annotation;
                }
            }
        }
        return $o;
    }

    /**
     * 单独解析特殊注解
     *
     * @param object|string $annotation              注解对象
     * @param null|array    $same_method_annotations 相同方法下的其他注解列表（可为数组或 null）
     */
    public function parseSpecial(object|string $annotation, ?array $same_method_annotations = null): ?bool
    {
        foreach (($this->special_parsers[$annotation::class] ?? []) as $parser) {
            $result = $parser($annotation, $same_method_annotations);
            if (is_bool($result)) {
                return $result;
            }
        }
        return null;
    }

    /**
     * 添加解析的路径
     *
     * @param string $path        注册解析注解的路径
     * @param string $indoor_name 起始命名空间的名称
     * @param array  $join_groups 此类命名空间解析出来的注解要加入的组
     */
    public function addPsr4Path(string $path, string $indoor_name, array $join_groups = []): void
    {
        logger()->debug('Add register path: ' . $path . ' => ' . $indoor_name);
        $all_class = FileSystem::getClassesPsr4($path, $indoor_name, return_path_value: false);
        if (!empty($join_groups)) {
            foreach ($all_class as $v) {
                $this->class_bind_group_list[$v] = $join_groups;
            }
        }
        $this->class_list = array_merge($this->class_list, $all_class);
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
    private function addRouteAnnotation(Route $vss, ?array $same_method_annotations = null)
    {
        // 拿到所属方法的类上面有没有控制器的注解
        $prefix = '';
        if ($same_method_annotations !== null) {
            foreach ($same_method_annotations as $annotation) {
                if ($annotation instanceof Controller) {
                    $prefix = $annotation->prefix;
                    break;
                }
            }
        }
        $tail = trim($vss->route, '/');
        $route_name = $prefix . ($tail === '' ? '' : '/') . $tail;
        logger()->debug('添加路由：' . $route_name);
        $route = new \Symfony\Component\Routing\Route($route_name, ['_class' => $vss->class, '_method' => $vss->method]);
        $route->setMethods($vss->request_method);

        HttpUtil::getRouteCollection()->add(md5($route_name), $route);
        return null;
    }
}
