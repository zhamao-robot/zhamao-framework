<?php


namespace ZM\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use ZM\Annotation\Interfaces\ErgodicAnnotation;
use ZM\Console\Console;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ZM\Annotation\Http\{HandleAfter, HandleBefore, HandleException, Middleware, MiddlewareClass, RequestMapping};
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Module\Closed;
use ZM\Http\RouteManager;

class AnnotationParser
{
    private $path_list = [];

    private $start_time;

    private $annotation_map = [];
    private $middleware_map = [];
    private $middlewares = [];

    /** @var null|AnnotationReader */
    private $reader = null;
    private $req_mapping = [];

    /**
     * AnnotationParser constructor.
     */
    public function __construct() {
        $this->start_time = microtime(true);
        //$this->loadAnnotationClasses();
        $this->req_mapping[0] = [
            'id' => 0,
            'pid' => -1,
            'name' => '/'
        ];
    }

    /**
     * 注册各个模块类的注解和模块level的排序
     * @throws ReflectionException
     */
    public function registerMods() {
        foreach ($this->path_list as $path) {
            Console::debug("parsing annotation in " . $path[0]);
            $all_class = getAllClasses($path[0], $path[1]);
            $this->reader = new AnnotationReader();
            foreach ($all_class as $v) {
                Console::debug("正在检索 " . $v);
                $reflection_class = new ReflectionClass($v);
                $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
                $class_annotations = $this->reader->getClassAnnotations($reflection_class);

                // 这段为新加的:start
                //这里将每个类里面所有的类注解、方法注解通通加到一颗大树上，后期解析
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
                $this->annotation_map[$v]["class_annotations"] = $class_annotations;
                $this->annotation_map[$v]["methods"] = $methods;
                foreach ($methods as $method) {
                    $this->annotation_map[$v]["methods_annotations"][$method->getName()] = $this->reader->getMethodAnnotations($method);
                }


                foreach ($this->annotation_map[$v]["class_annotations"] as $ks => $vs) {
                    $vs->class = $v;

                    //预处理1：将适用于每一个函数的注解到类注解重新注解到每个函数下面
                    if ($vs instanceof ErgodicAnnotation) {
                        foreach (($this->annotation_map[$v]["methods"] ?? []) as $method) {
                            $copy = clone $vs;
                            /** @noinspection PhpUndefinedFieldInspection */
                            $copy->method = $method->getName();
                            $this->annotation_map[$v]["methods_annotations"][$method->getName()][] = $copy;
                        }
                    }

                    //预处理2：处理 class 下面的注解
                    if ($vs instanceof Closed) {
                        unset($this->annotation_map[$v]);
                        continue 2;
                    } elseif ($vs instanceof MiddlewareClass) {
                        Console::debug("正在注册中间件 " . $reflection_class->getName());
                        $rs = $this->registerMiddleware($vs, $reflection_class);
                        $this->middlewares[$rs["name"]] = $rs;
                    }
                }

                //预处理3：处理每个函数上面的特殊注解，就是需要操作一些东西的
                foreach (($this->annotation_map[$v]["methods_annotations"] ?? []) as $method_name => $methods_annotations) {
                    foreach ($methods_annotations as $method_anno) {
                        /** @var AnnotationBase $method_anno */
                        $method_anno->class = $v;
                        $method_anno->method = $method_name;
                        if ($method_anno instanceof RequestMapping) {
                            RouteManager::importRouteByAnnotation($method_anno, $method_name, $v, $methods_annotations);
                        } elseif ($method_anno instanceof Middleware) {
                            $this->middleware_map[$method_anno->class][$method_anno->method][] = $method_anno->middleware;
                        }
                    }
                }
            }
        }
        Console::debug("解析注解完毕！");
    }

    /**
     * @return array
     */
    public function generateAnnotationEvents() {
        $o = [];
        foreach ($this->annotation_map as $module => $obj) {
            foreach (($obj["class_annotations"] ?? []) as $class_annotation) {
                if ($class_annotation instanceof ErgodicAnnotation) continue;
                else $o[get_class($class_annotation)][] = $class_annotation;
            }
            foreach (($obj["methods_annotations"] ?? []) as $method_name => $methods_annotations) {
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
     * @return array
     */
    public function getMiddlewares() { return $this->middlewares; }

    /**
     * @return array
     */
    public function getMiddlewareMap() { return $this->middleware_map; }

    /**
     * @return array
     */
    public function getReqMapping() { return $this->req_mapping; }

    /**
     * @param $path
     * @param $indoor_name
     */
    public function addRegisterPath($path, $indoor_name) { $this->path_list[] = [$path, $indoor_name]; }

    //private function below

    private function registerMiddleware(MiddlewareClass $vs, ReflectionClass $reflection_class) {
        $result = [
            "class" => "\\" . $reflection_class->getName(),
            "name" => $vs->name
        ];

        foreach ($reflection_class->getMethods() as $vss) {
            $method_annotations = $this->reader->getMethodAnnotations($vss);
            foreach ($method_annotations as $vsss) {
                if ($vsss instanceof HandleBefore) $result["before"] = $vss->getName();
                if ($vsss instanceof HandleAfter) $result["after"] = $vss->getName();
                if ($vsss instanceof HandleException) {
                    $result["exceptions"][$vsss->class_name] = $vss->getName();
                }
            }
        }
        return $result;
    }

    public function sortByLevel(&$events, string $class_name, $prefix = "") {
        if (is_a($class_name, Level::class, true)) {
            $class_name .= $prefix;
            usort($events[$class_name], function ($a, $b) {
                $left = $a->level;
                $right = $b->level;
                return $left > $right ? -1 : ($left == $right ? 0 : 1);
            });
        }
    }
}
