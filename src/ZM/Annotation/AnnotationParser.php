<?php


namespace ZM\Annotation;

use Doctrine\Common\Annotations\{AnnotationException, AnnotationReader};
use Co;
use Framework\{Console, ZMBuf};
use Error;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ZM\Annotation\CQ\{CQAfter,
    CQAPIResponse,
    CQAPISend,
    CQBefore,
    CQCommand,
    CQMessage,
    CQMetaEvent,
    CQNotice,
    CQRequest
};
use ZM\Annotation\Http\{After, Before, Controller, HandleException, Middleware, MiddlewareClass, RequestMapping};
use Swoole\Timer;
use ZM\Annotation\Interfaces\CustomAnnotation;
use ZM\Annotation\Interfaces\Level;
use ZM\Annotation\Module\{Closed, InitBuffer, LoadBuffer, SaveBuffer};
use ZM\Annotation\Swoole\{OnSave, OnStart, OnTick, SwooleEventAfter, SwooleEventAt};
use ZM\Annotation\Interfaces\Rule;
use ZM\Connection\WSConnection;
use ZM\Event\EventHandler;
use ZM\Http\MiddlewareInterface;
use Framework\DataProvider;
use ZM\Utils\ZMUtil;

class AnnotationParser
{
    /**
     * 注册各个模块类的注解和模块level的排序
     * @throws ReflectionException
     * @throws AnnotationException
     */
    public static function registerMods() {
        self::loadAnnotationClasses();
        $all_class = getAllClasses(DataProvider::getWorkingDir() . "/src/Module/", "Module");
        ZMBuf::$req_mapping[0] = [
            'id' => 0,
            'pid' => -1,
            'name' => '/'
        ];
        $reader = new AnnotationReader();
        foreach ($all_class as $v) {
            Console::debug("正在检索 " . $v);
            $reflection_class = new ReflectionClass($v);
            $class_prefix = '';
            $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
            $class_annotations = $reader->getClassAnnotations($reflection_class);
            $middleware_addon = null;
            foreach ($class_annotations as $vs) {
                if ($vs instanceof Closed) {
                    continue 2;
                } elseif ($vs instanceof Controller) {
                    Console::debug("找到 Controller 中间件: " . $vs->class);
                    $class_prefix = $vs->prefix;
                } elseif ($vs instanceof SaveBuffer) {
                    Console::debug("注册自动保存的缓存变量: " . $vs->buf_name . " (Dir:" . $vs->sub_folder . ")");
                    DataProvider::addSaveBuffer($vs->buf_name, $vs->sub_folder);
                } elseif ($vs instanceof LoadBuffer) {
                    Console::debug("注册到内存的缓存变量: " . $vs->buf_name . " (Dir:" . $vs->sub_folder . ")");
                    ZMBuf::set($vs->buf_name, DataProvider::getJsonData(($vs->sub_folder ?? "") . "/" . $vs->buf_name . ".json"));
                } elseif ($vs instanceof InitBuffer) {
                    ZMBuf::set($vs->buf_name, []);
                } elseif ($vs instanceof MiddlewareClass) {
                    Console::verbose("正在注册中间件 " . $reflection_class->getName());
                    $result = [
                        "class" => "\\" . $reflection_class->getName()
                    ];
                    foreach ($methods as $vss) {
                        if ($vss->getName() == "getName") {
                            /** @var MiddlewareInterface $tmp */
                            $tmp = new $v();
                            $result["name"] = $tmp->getName();
                            continue;
                        }
                        $method_annotations = $reader->getMethodAnnotations($vss);
                        foreach ($method_annotations as $vsss) {
                            if ($vss instanceof Rule) $vss = self::registerRuleEvent($vsss, $vss, $reflection_class);
                            else $vss = self::registerMethod($vsss, $vss, $reflection_class);
                            //echo get_class($vsss) . PHP_EOL;
                            if ($vsss instanceof Before) $result["before"] = $vsss->method;
                            if ($vsss instanceof After) $result["after"] = $vsss->method;
                            if ($vsss instanceof HandleException) {
                                $result["exceptions"][$vsss->class_name] = $vsss->method;
                            }
                        }
                    }
                    ZMBuf::$events[MiddlewareClass::class][$result["name"]] = $result;
                    continue 2;
                } elseif ($vs instanceof Middleware) {
                    $middleware_addon = $vs;
                } elseif ($vs instanceof CustomAnnotation) {
                    $vs->class = $reflection_class->getName();
                    ZMBuf::$events[get_class($vs)][] = $vs;
                }
            }
            foreach ($methods as $vs) {
                if ($middleware_addon !== null) {
                    Console::debug("Added middleware " . $middleware_addon->middleware . " to $v -> " . $vs->getName());
                    ZMBuf::$events[MiddlewareInterface::class][$v][$vs->getName()][] = $middleware_addon->middleware;
                }
                $method_annotations = $reader->getMethodAnnotations($vs);
                foreach ($method_annotations as $vss) {
                    if ($vss instanceof Rule) $vss = self::registerRuleEvent($vss, $vs, $reflection_class);
                    else $vss = self::registerMethod($vss, $vs, $reflection_class);
                    Console::debug("寻找 " . $vs->getName() . " -> " . get_class($vss));

                    if ($vss instanceof SwooleEventAt) ZMBuf::$events[SwooleEventAt::class][] = $vss;
                    elseif ($vss instanceof SwooleEventAfter) ZMBuf::$events[SwooleEventAfter::class][] = $vss;
                    elseif ($vss instanceof CQMessage) ZMBuf::$events[CQMessage::class][] = $vss;
                    elseif ($vss instanceof CQNotice) ZMBuf::$events[CQNotice::class][] = $vss;
                    elseif ($vss instanceof CQRequest) ZMBuf::$events[CQRequest::class][] = $vss;
                    elseif ($vss instanceof CQMetaEvent) ZMBuf::$events[CQMetaEvent::class][] = $vss;
                    elseif ($vss instanceof CQCommand) ZMBuf::$events[CQCommand::class][] = $vss;
                    elseif ($vss instanceof RequestMapping) {
                        self::registerRequestMapping($vss, $vs, $reflection_class, $class_prefix);
                    } elseif ($vss instanceof CustomAnnotation) ZMBuf::$events[get_class($vss)][] = $vss;
                    elseif ($vss instanceof CQBefore) ZMBuf::$events[CQBefore::class][$vss->cq_event][] = $vss;
                    elseif ($vss instanceof CQAfter) ZMBuf::$events[CQAfter::class][$vss->cq_event][] = $vss;
                    elseif ($vss instanceof OnStart) ZMBuf::$events[OnStart::class][] = $vss;
                    elseif ($vss instanceof OnSave) ZMBuf::$events[OnSave::class][] = $vss;
                    elseif ($vss instanceof Middleware) ZMBuf::$events[MiddlewareInterface::class][$vss->class][$vss->method][] = $vss->middleware;
                    elseif ($vss instanceof OnTick) self::addTimerTick($vss);
                    elseif ($vss instanceof CQAPISend) ZMBuf::$events[CQAPISend::class][] = $vss;
                    elseif ($vss instanceof CQAPIResponse) ZMBuf::$events[CQAPIResponse::class][$vss->retcode] = [$vss->class, $vss->method];
                }
            }
        }
        $tree = self::genTree(ZMBuf::$req_mapping);
        ZMBuf::$req_mapping = $tree[0];
        //给支持level的排个序
        foreach (ZMBuf::$events as $class_name => $v) {
            if (is_a($class_name, Level::class, true)) {
                for ($i = 0; $i < count(ZMBuf::$events[$class_name]) - 1; ++$i) {
                    for ($j = 0; $j < count(ZMBuf::$events[$class_name]) - $i - 1; ++$j) {
                        $l1 = ZMBuf::$events[$class_name][$j]->level;
                        $l2 = ZMBuf::$events[$class_name][$j + 1]->level;
                        if ($l1 < $l2) {
                            $t = ZMBuf::$events[$class_name][$j + 1];
                            ZMBuf::$events[$class_name][$j + 1] = ZMBuf::$events[$class_name][$j];
                            ZMBuf::$events[$class_name][$j] = $t;
                        }
                    }
                }
            }
        }
        Console::debug("解析注解完毕！");
        if (ZMBuf::isset("timer_count")) {
            Console::info("Added " . ZMBuf::get("timer_count") . " timer(s)!");
            ZMBuf::unsetCache("timer_count");
        }
    }

    public static function getRuleCallback($rule_str) {
        $func = null;
        $rule = $rule_str;
        if ($rule != "") {
            $asp = explode(":", $rule);
            $asp_name = array_shift($asp);
            $rest = implode(":", $asp);
            //Swoole 事件时走此switch
            switch ($asp_name) {
                case "connectType": //websocket连接类型
                    $func = function (?WSConnection $connection) use ($rest) {
                        if ($connection === null) return false;
                        return $connection->getType() == $rest ? true : false;
                    };
                    break;
                case "containsGet": //handle http request事件时才能用
                case "containsPost":
                    $get_list = explode(",", $rest);
                    if ($asp_name == "containsGet")
                        $func = function ($request) use ($get_list) {
                            foreach ($get_list as $v) if (!isset($request->get[$v])) return false;
                            return true;
                        };
                    else
                        $func = function ($request) use ($get_list) {
                            foreach ($get_list as $v) if (!isset($request->post[$v])) return false;
                            return true;
                        };
                    /*
                    if ($controller_prefix != '') {
                        $p = ZMBuf::$req_mapping_node;
                        $prefix_exp = explode("/", $controller_prefix);
                        foreach ($prefix_exp as $k => $v) {
                            if ($v == "" || $v == ".." || $v == ".") {
                                unset($prefix_exp[$k]);
                            }
                        }
                        while (($shift = array_shift($prefix_exp)) !== null) {
                            $p->addRoute($shift, new MappingNode($shift));
                            $p = $p->getRoute($shift);
                        }
                        if ($p->getNodeName() != "/") {
                            $p->setMethod($method->getName());
                            $p->setClass($class->getName());
                            $p->setRule($func);
                            return "mapped";
                        }
                    }*/
                    break;
                case "containsJson": //handle http request事件时才能用
                    $json_list = explode(",", $rest);
                    $func = function ($json) use ($json_list) {
                        foreach ($json_list as $v) if (!isset($json[$v])) return false;
                        return true;
                    };
                    break;
                case "dataEqual": //handle websocket message事件时才能用
                    $func = function ($data) use ($rest) {
                        return $data == $rest;
                    };
                    break;
            }
            switch ($asp_name) {
                case "msgMatch": //handle cq message事件时才能用
                    $func = function ($msg) use ($rest) {
                        return matchPattern($rest, $msg);
                    };
                    break;
                case "msgEqual": //handle cq message事件时才能用
                    $func = function ($msg) use ($rest) {
                        return trim($msg) == $rest;
                    };
                    break;

            }
        }
        return $func;
    }

    public static function registerRuleEvent(?AnnotationBase $vss, ReflectionMethod $method, ReflectionClass $class) {
        $vss->callback = self::getRuleCallback($vss->getRule());
        $vss->method = $method->getName();
        $vss->class = $class->getName();
        return $vss;
    }

    public static function registerMethod(?AnnotationBase $vss, ReflectionMethod $method, ReflectionClass $class) {
        $vss->method = $method->getName();
        $vss->class = $class->getName();
        return $vss;
    }

    private static function registerRequestMapping(RequestMapping $vss, ReflectionMethod $method, ReflectionClass $class, string $prefix) {
        $array = ZMBuf::$req_mapping;
        $uid = count($array);
        $prefix_exp = explode("/", $prefix);
        $route_exp = explode("/", $vss->route);
        foreach ($prefix_exp as $k => $v) {
            if ($v == "" || $v == ".." || $v == ".") {
                unset($prefix_exp[$k]);
            }
        }
        foreach ($route_exp as $k => $v) {
            if ($v == "" || $v == ".." || $v == ".") {
                unset($route_exp[$k]);
            }
        }
        if ($prefix_exp == [] && $route_exp == []) {
            $array[0]['method'] = $method->getName();
            $array[0]['class'] = $class->getName();
            $array[0]['request_method'] = $vss->request_method;
            ZMBuf::$req_mapping = $array;
            return;
        }
        $pid = 0;
        while (($shift = array_shift($prefix_exp)) !== null) {
            foreach ($array as $k => $v) {
                if ($v["name"] == $shift && $pid == ($v["pid"] ?? -1)) {
                    $pid = $v["id"];
                    continue 2;
                }
            }
            $array[$uid++] = [
                'id' => $uid - 1,
                'pid' => $pid,
                'name' => $shift
            ];
            $pid = $uid - 1;
        }
        while (($shift = array_shift($route_exp)) !== null) {
            /*if (mb_substr($shift, 0, 1) == "{" && mb_substr($shift, -1, 1) == "}") {
                $p->removeAllRoute();
                Console::info("移除本节点其他所有路由中");
            }*/
            foreach ($array as $k => $v) {
                if ($v["name"] == $shift && $pid == ($v["pid"] ?? -1)) {
                    $pid = $v["id"];
                    continue 2;
                }
            }
            if (mb_substr($shift, 0, 1) == "{" && mb_substr($shift, -1, 1) == "}") {
                foreach ($array as $k => $v) {
                    if ($pid == $v["id"]) {
                        $array[$k]["param_route"] = $uid;
                    }
                }
            }
            $array[$uid++] = [
                'id' => $uid - 1,
                'pid' => $pid,
                'name' => $shift
            ];
            $pid = $uid - 1;
        }
        $array[$uid - 1]['method'] = $method->getName();
        $array[$uid - 1]['class'] = $class->getName();
        $array[$uid - 1]['request_method'] = $vss->request_method;
        ZMBuf::$req_mapping = $array;
    }

    private static function loadAnnotationClasses() {
        $class = getAllClasses(WORKING_DIR . "/src/ZM/Annotation/", "ZM\\Annotation");
        foreach ($class as $v) {
            $s = WORKING_DIR . '/src/' . str_replace("\\", "/", $v) . ".php";
            require_once $s;
        }
        $class = getAllClasses(DataProvider::getWorkingDir() . "/src/Custom/Annotation/", "Custom\\Annotation");
        foreach ($class as $v) {
            $s = DataProvider::getWorkingDir() . '/src/' . str_replace("\\", "/", $v) . ".php";
            Console::debug("Requiring custom annotation " . $s);
            require_once $s;
        }
    }

    public static function genTree($items) {
        $tree = array();
        foreach ($items as $item)
            if (isset($items[$item['pid']]))
                $items[$item['pid']]['son'][] = &$items[$item['id']];
            else
                $tree[] = &$items[$item['id']];
        return $tree;
    }

    private static function addTimerTick(?OnTick $vss) {
        ZMBuf::set("timer_count", ZMBuf::get("timer_count", 0) + 1);
        $class = ZMUtil::getModInstance($vss->class);
        $method = $vss->method;
        $ms = $vss->tick_ms;
        $cid = go(function () use ($class, $method, $ms) {
            Co::suspend();
            $plain_class = get_class($class);
            if (!isset(ZMBuf::$events[MiddlewareInterface::class][$plain_class][$method])) {
                Console::debug("Added timer: " . $plain_class . " -> " . $method);
                Timer::tick($ms, function () use ($class, $method) {
                    set_coroutine_params([]);
                    try {
                        $class->$method();
                    } catch (Exception $e) {
                        Console::error("Uncaught error from TimerTick: " . $e->getMessage() . " at " . $e->getFile() . "({$e->getLine()})");
                    } catch (Error $e) {
                        Console::error("Uncaught fatal error from TimerTick: " . $e->getMessage());
                        echo Console::setColor($e->getTraceAsString(), "gray");
                        Console::error("Please check your code!");
                    }
                });
            } else {
                Console::debug("Added Middleware-based timer: " . $plain_class . " -> " . $method);
                Timer::tick($ms, function () use ($class, $method) {
                    set_coroutine_params([]);
                    try {
                        EventHandler::callWithMiddleware($class, $method, [], []);
                    } catch (Exception $e) {
                        Console::error("Uncaught error from TimerTick: " . $e->getMessage() . " at " . $e->getFile() . "({$e->getLine()})");
                    } catch (Error $e) {
                        Console::error("Uncaught fatal error from TimerTick: " . $e->getMessage());
                        echo Console::setColor($e->getTraceAsString(), "gray");
                        Console::error("Please check your code!");
                    }
                });
            }
        });
        ZMBuf::append("paused_tick", $cid);
    }
}
