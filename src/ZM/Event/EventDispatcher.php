<?php


namespace ZM\Event;


use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use ZM\Annotation\AnnotationBase;
use ZM\Console\Console;
use ZM\Exception\InterruptException;
use ZM\Exception\ZMException;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\ZMUtil;

class EventDispatcher
{
    /** @var string */
    private $class;
    /** @var null|callable */
    private $rule = null;
    /** @var null|callable */
    private $return_func = null;
    /** @var bool */
    private $log = false;
    /** @var int */
    private $eid = 0;

    /**
     * @param null $return_var
     * @throws InterruptException
     */
    public static function interrupt($return_var = null) {
        throw new InterruptException($return_var);
    }

    public static function enableEventTrace($event_class) {
        SpinLock::lock("_event_trace");
        $list = LightCacheInside::get("wait_api", "event_trace");
        $list[$event_class] = true;
        LightCacheInside::set("wait_api", "event_trace", $list);
        SpinLock::unlock("_event_trace");
    }

    public static function disableEventTrace($event_class) {
        SpinLock::lock("_event_trace");
        $list = LightCacheInside::get("wait_api", "event_trace");
        unset($list[$event_class]);
        LightCacheInside::set("wait_api", "event_trace", $list);
        SpinLock::unlock("_event_trace");
    }

    public function __construct(string $class = '') {
        $this->class = $class;
        try {
            $this->eid = ZMAtomic::get("_event_id")->add(1);
            $list = LightCacheInside::get("wait_api", "event_trace");
        } catch (ZMException $e) {
            $list = [];
        }
        if (isset($list[$class])) $this->log = true;
        if ($this->log) Console::verbose("[事件分发{$this->eid}] 开始分发事件: " . $class);
    }

    public function setRuleFunction(callable $rule = null) {
        $this->rule = $rule;
        return $this;
    }

    public function setReturnFunction(callable $return_func) {
        $this->return_func = $return_func;
        return $this;
    }

    public function dispatchEvents(...$params) {
        try {
            foreach ((EventManager::$events[$this->class] ?? []) as $v) {
                $result = $this->dispatchEvent($v, $this->rule, ...$params);
                if ($this->log) Console::verbose("[事件分发{$this->eid}] 单一对象 " . $v->class . "::" . $v->method . " 分发结束。");
                if ($result !== false && is_callable($this->return_func)) {
                    if ($this->log) Console::verbose("[事件分发{$this->eid}] 单一对象 " . $v->class . "::" . $v->method . " 正在执行返回值处理函数 ...");
                    ($this->return_func)($result);
                }
            }
            return true;
        } catch (InterruptException $e) {
            return $e->return_var;
        } catch (AnnotationException $e) {
            return false;
        }
    }

    /**
     * @param AnnotationBase|null $v
     * @param null $rule_func
     * @param mixed ...$params
     * @throws AnnotationException
     * @throws InterruptException
     * @return mixed
     */
    public function dispatchEvent(?AnnotationBase $v, $rule_func = null, ...$params) {
        $q_c = $v->class;
        $q_f = $v->method;
        if ($this->log) Console::verbose("[事件分发{$this->eid}] 正在判断 " . $q_c . "::" . $q_f . " 方法下的 rule ...");
        if ($rule_func !== null && !$rule_func($v)) {
            if ($this->log) Console::verbose("[事件分发{$this->eid}] " . $q_c . "::" . $q_f . " 方法下的 rule 判断为 false, 拒绝执行此方法。");
            return false;
        }
        if ($this->log) Console::verbose("[事件分发{$this->eid}] " . $q_c . "::" . $q_f . " 方法下的 rule 为真，继续执行方法本身 ...");
        if (isset(EventManager::$middleware_map[$q_c][$q_f])) {
            $middlewares = EventManager::$middleware_map[$q_c][$q_f];
            if ($this->log) Console::verbose("[事件分发{$this->eid}] " . $q_c . "::" . $q_f . " 方法还绑定了 Middleware：" . implode(", ", $middlewares));
            $before_result = true;
            $r = [];
            foreach ($middlewares as $k => $middleware) {
                if (!isset(EventManager::$middlewares[$middleware])) throw new AnnotationException("Annotation parse error: Unknown MiddlewareClass named \"{$middleware}\"!");
                $middleware_obj = EventManager::$middlewares[$middleware];
                $before = $middleware_obj["class"];
                //var_dump($middleware_obj);
                $r[$k] = new $before();
                $r[$k]->class = $q_c;
                $r[$k]->method = $q_f;
                if (isset($middleware_obj["before"])) {
                    if ($this->log) Console::verbose("[事件分发{$this->eid}] Middleware 存在前置事件，执行中 ...");
                    $rs = $middleware_obj["before"];
                    $before_result = $r[$k]->$rs(...$params);
                    if ($before_result === false) {
                        if ($this->log) Console::verbose("[事件分发{$this->eid}] Middleware 前置事件为 false，停止执行原事件，开始执行下一事件。");
                        break;
                    } else {
                        if ($this->log) Console::verbose("[事件分发{$this->eid}] Middleware 前置事件为 true，继续执行原事件。");
                    }
                }
            }
            if ($before_result) {
                try {
                    $q_o = ZMUtil::getModInstance($q_c);
                    if ($this->log) Console::verbose("[事件分发{$this->eid}] 正在执行方法 " . $q_c . "::" . $q_f . " ...");
                    $result = $q_o->$q_f(...$params);
                } catch (Exception $e) {
                    if ($e instanceof InterruptException) {
                        if ($this->log) Console::verbose("[事件分发{$this->eid}] 检测到事件阻断调用，正在跳出事件分发器 ...");
                        throw $e;
                    }
                    if ($this->log) Console::verbose("[事件分发{$this->eid}] 方法 " . $q_c . "::" . $q_f . " 执行过程中抛出了异常，正在倒序查找 Middleware 中的捕获方法 ...");
                    for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                        $middleware_obj = EventManager::$middlewares[$middlewares[$i]];
                        if (!isset($middleware_obj["exceptions"])) continue;
                        foreach ($middleware_obj["exceptions"] as $name => $method) {
                            if ($e instanceof $name) {
                                if ($this->log) Console::verbose("[事件分发{$this->eid}] 方法 " . $q_c . "::" . $q_f . " 的异常 " . get_class($e) . " 被 Middleware:" . $middlewares[$i] . " 下的 " . get_class($r[$i]) . "::" . $method . " 捕获。");
                                $r[$i]->$method($e);
                                self::interrupt();
                            }
                        }
                    }
                    throw $e;
                }
                for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                    $middleware_obj = EventManager::$middlewares[$middlewares[$i]];
                    if (isset($middleware_obj["after"], $r[$i])) {
                        if ($this->log) Console::verbose("[事件分发{$this->eid}] Middleware 存在后置事件，执行中 ...");
                        $r[$i]->{$middleware_obj["after"]}(...$params);
                        if ($this->log) Console::verbose("[事件分发{$this->eid}] Middleware 后置事件执行完毕！");
                    }
                }
                return $result;
            }
            return false;
        } else {
            $q_o = ZMUtil::getModInstance($q_c);
            if ($this->log) Console::verbose("[事件分发{$this->eid}] 正在执行方法 " . $q_c . "::" . $q_f . " ...");
            return $q_o->$q_f(...$params);
        }
    }
}
