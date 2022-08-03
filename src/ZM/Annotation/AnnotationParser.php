<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Koriym\Attributes\AttributeReader;
use Koriym\Attributes\DualReader;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ZM\Annotation\Http\HandleAfter;
use ZM\Annotation\Http\HandleBefore;
use ZM\Annotation\Http\HandleException;
use ZM\Annotation\Http\Middleware;
use ZM\Annotation\Http\MiddlewareClass;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Interfaces\ErgodicAnnotation;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Module\Closed;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\EventManager;
use ZM\Exception\AnnotationException;
use ZM\Utils\Manager\RouteManager;
use ZM\Utils\ZMUtil;

use function server;

class AnnotationParser
{
    private $path_list = [];

    private $start_time;

    private $annotation_map = [];

    private $middleware_map = [];

    private $middlewares = [];

    /** @var null|AnnotationReader|DualReader */
    private $reader;

    private $req_mapping = [];

    /**
     * AnnotationParser constructor.
     */
    public function __construct()
    {
        $this->start_time = microtime(true);
        // $this->loadAnnotationClasses();
        $this->req_mapping[0] = [
            'id' => 0,
            'pid' => -1,
            'name' => '/',
        ];
    }

    /**
     * 注册各个模块类的注解和模块level的排序
     * @throws ReflectionException
     */
    public function registerMods()
    {
        foreach ($this->path_list as $path) {
            Console::debug('parsing annotation in ' . $path[0] . ':' . $path[1]);
            $all_class = ZMUtil::getClassesPsr4($path[0], $path[1]);

            $conf = ZMConfig::get('global', 'runtime')['annotation_reader_ignore'] ?? [];
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
            AnnotationReader::addGlobalIgnoredName('mixin');
            $this->reader = new DualReader(new AnnotationReader(), new AttributeReader());
            foreach ($all_class as $v) {
                Console::debug('正在检索 ' . $v);

                $reflection_class = new ReflectionClass($v);
                $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
                $class_annotations = $this->reader->getClassAnnotations($reflection_class);
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

                // 生成主树
                $this->annotation_map[$v]['class_annotations'] = $class_annotations;
                $this->annotation_map[$v]['methods'] = $methods;
                foreach ($methods as $method) {
                    $this->annotation_map[$v]['methods_annotations'][$method->getName()] = $this->reader->getMethodAnnotations($method);
                }

                foreach ($this->annotation_map[$v]['class_annotations'] as $vs) {
                    $vs->class = $v;

                    // 预处理1：将适用于每一个函数的注解到类注解重新注解到每个函数下面
                    if (($vs instanceof ErgodicAnnotation) && ($vs instanceof AnnotationBase)) {
                        foreach (($this->annotation_map[$v]['methods'] ?? []) as $method) {
                            $copy = clone $vs;
                            $copy->method = $method->getName();
                            $this->annotation_map[$v]['methods_annotations'][$method->getName()][] = $copy;
                        }
                    }

                    // 预处理2：处理 class 下面的注解
                    if ($vs instanceof Closed) {
                        unset($this->annotation_map[$v]);
                        continue 2;
                    }
                    if ($vs instanceof MiddlewareClass) {
                        // 注册中间件本身的类，标记到 middlewares 属性中
                        Console::debug('正在注册中间件 ' . $reflection_class->getName());
                        $rs = $this->registerMiddleware($vs, $reflection_class);
                        $this->middlewares[$rs['name']] = $rs;
                    }
                }

                $inserted = [];

                // 预处理3：处理每个函数上面的特殊注解，就是需要操作一些东西的
                foreach (($this->annotation_map[$v]['methods_annotations'] ?? []) as $method_name => $methods_annotations) {
                    foreach ($methods_annotations as $method_anno) {
                        /* @var AnnotationBase $method_anno */
                        $method_anno->class = $v;
                        $method_anno->method = $method_name;
                        if (!($method_anno instanceof Middleware) && ($middlewares = ZMConfig::get('global', 'runtime')['global_middleware_binding'][get_class($method_anno)] ?? []) !== []) {
                            if (!isset($inserted[$v][$method_name])) {
                                // 在这里在其他中间件前插入插入全局的中间件
                                foreach ($middlewares as $middleware) {
                                    $mid_class = new Middleware($middleware);
                                    $mid_class->class = $v;
                                    $mid_class->method = $method_name;
                                    $this->middleware_map[$v][$method_name][] = $mid_class;
                                }
                                $inserted[$v][$method_name] = true;
                            }
                        } elseif ($method_anno instanceof RequestMapping) {
                            RouteManager::importRouteByAnnotation($method_anno, $method_name, $v, $methods_annotations);
                        } elseif ($method_anno instanceof Middleware) {
                            $this->middleware_map[$method_anno->class][$method_anno->method][] = $method_anno;
                        } else {
                            EventManager::$event_map[$method_anno->class][$method_anno->method][] = $method_anno;
                        }
                    }
                }
            }
        }
        Console::debug('解析注解完毕！');
    }

    public function generateAnnotationEvents(): array
    {
        $o = [];
        foreach ($this->annotation_map as $obj) {
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

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getMiddlewareMap(): array
    {
        return $this->middleware_map;
    }

    public function getReqMapping(): array
    {
        return $this->req_mapping;
    }

    /**
     * @param string $path        注册解析注解的路径
     * @param string $indoor_name 起始命名空间的名称
     */
    public function addRegisterPath(string $path, string $indoor_name)
    {
        if (server()->worker_id === 0) {
            Console::verbose('Add register path: ' . $path . ' => ' . $indoor_name);
        }
        $this->path_list[] = [$path, $indoor_name];
    }

    /**
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
                $left = $a->level;
                $right = $b->level;
                return $left > $right ? -1 : ($left == $right ? 0 : 1);
            });
        }
    }

    /**
     * @throws AnnotationException
     */
    public function verifyMiddlewares()
    {
        if ((ZMConfig::get('global', 'runtime')['middleware_error_policy'] ?? 1) === 2) {
            // 我承认套三层foreach很不优雅，但是这个会很快的。
            foreach ($this->middleware_map as $v) {
                foreach ($v as $vs) {
                    foreach ($vs as $mid) {
                        if (!isset($this->middlewares[$mid->middleware])) {
                            throw new AnnotationException("Annotation parse error: Unknown MiddlewareClass named \"{$mid->middleware}\"!");
                        }
                    }
                }
            }
        }
    }

    public function getRunTime()
    {
        return microtime(true) - $this->start_time;
    }

    // private function below

    private function registerMiddleware(MiddlewareClass $vs, ReflectionClass $reflection_class): array
    {
        $result = [
            'class' => '\\' . $reflection_class->getName(),
            'name' => $vs->name,
        ];

        foreach ($reflection_class->getMethods() as $vss) {
            $method_annotations = $this->reader->getMethodAnnotations($vss);
            foreach ($method_annotations as $vsss) {
                if ($vsss instanceof HandleBefore) {
                    $result['before'] = $vss->getName();
                }
                if ($vsss instanceof HandleAfter) {
                    $result['after'] = $vss->getName();
                }
                if ($vsss instanceof HandleException) {
                    $result['exceptions'][$vsss->class_name] = $vss->getName();
                }
            }
        }
        return $result;
    }
}
