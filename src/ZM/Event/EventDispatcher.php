<?php

declare(strict_types=1);
/** @noinspection PhpUnused */

namespace ZM\Event;

use Closure;
use Doctrine\Common\Annotations\AnnotationException;
use Error;
use Exception;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Exception\InterruptException;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\ZMUtil;

class EventDispatcher
{
    public const STATUS_NORMAL = 0;            // 正常结束

    public const STATUS_INTERRUPTED = 1;       // 被interrupt了，不管在什么地方

    public const STATUS_EXCEPTION = 2;         // 执行过程中抛出了异常

    public const STATUS_BEFORE_FAILED = 3;     // 中间件HandleBefore返回了false，所以不执行此方法

    public const STATUS_RULE_FAILED = 4;       // 判断事件执行的规则函数判定为false，所以不执行此方法

    /** @var int */
    public $status = self::STATUS_NORMAL;

    /** @var mixed */
    public $store;

    /** @var string */
    private $class;

    /** @var null|callable */
    private $rule;

    /** @var null|callable */
    private $return_func;

    /** @var bool */
    private $log = false;

    /** @var int */
    private $eid;

    public function __construct(string $class = '')
    {
        $this->class = $class;
        $this->eid = ZMAtomic::get('_event_id')->add(1);
        $list = LightCacheInside::get('wait_api', 'event_trace');
        if (isset($list[$class])) {
            $this->log = true;
        }
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] 开始分发事件: " . $class);
        }
    }

    /**
     * @param  mixed              $return_var
     * @throws InterruptException
     */
    public static function interrupt($return_var = null)
    {
        throw new InterruptException($return_var);
    }

    public static function enableEventTrace($event_class)
    {
        SpinLock::lock('_event_trace');
        $list = LightCacheInside::get('wait_api', 'event_trace');
        $list[$event_class] = true;
        LightCacheInside::set('wait_api', 'event_trace', $list);
        SpinLock::unlock('_event_trace');
    }

    public static function disableEventTrace($event_class)
    {
        SpinLock::lock('_event_trace');
        $list = LightCacheInside::get('wait_api', 'event_trace');
        unset($list[$event_class]);
        LightCacheInside::set('wait_api', 'event_trace', $list);
        SpinLock::unlock('_event_trace');
    }

    public function setRuleFunction(callable $rule = null): EventDispatcher
    {
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] 设置事件rule: " . $this->class);
        }
        $this->rule = $rule;
        return $this;
    }

    public function setReturnFunction(callable $return_func): EventDispatcher
    {
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] 设置事件returnFunc: " . $this->class);
        }
        $this->return_func = $return_func;
        return $this;
    }

    /**
     * @param  mixed     ...$params
     * @throws Exception
     */
    public function dispatchEvents(...$params)
    {
        try {
            foreach ((EventManager::$events[$this->class] ?? []) as $v) {
                $this->dispatchEvent($v, $this->rule, ...$params);
                if ($this->log) {
                    Console::verbose("[事件分发{$this->eid}] 单一对象 " . $v->class . '::' . (is_string($v->method) ? $v->method : '{closure}') . ' 分发结束。');
                }
                if ($this->status == self::STATUS_BEFORE_FAILED || $this->status == self::STATUS_RULE_FAILED) {
                    continue;
                }
                if (is_callable($this->return_func) && $this->status === self::STATUS_NORMAL) {
                    if ($this->log) {
                        Console::verbose("[事件分发{$this->eid}] 单一对象 " . $v->class . '::' . $v->method . ' 正在执行返回值处理函数 ...');
                    }
                    ($this->return_func)($this->store);
                }
            }
            if ($this->status === self::STATUS_RULE_FAILED) {
                $this->status = self::STATUS_NORMAL;
            }
            // TODO:没有过滤before的false，可能会导致一些问题，先观望一下
        } catch (InterruptException $e) {
            $this->store = $e->return_var;
            $this->status = self::STATUS_INTERRUPTED;
        } catch (Exception|Error $e) {
            $this->status = self::STATUS_EXCEPTION;
            throw $e;
        }
    }

    /**
     * @param  mixed               $v
     * @param  null                $rule_func
     * @param  mixed               ...$params
     * @throws InterruptException
     * @throws AnnotationException
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function dispatchEvent($v, $rule_func = null, ...$params)
    {
        $q_c = $v->class;
        $q_f = $v->method;
        if (($q_c ?? '') === '' && ($q_f instanceof Closure)) {
            if ($this->log) {
                Console::verbose("[事件分发{$this->eid}] 闭包函数的事件触发过程！");
            }
            if ($rule_func !== null && !$rule_func($v)) {
                if ($this->log) {
                    Console::verbose("[事件分发{$this->eid}] 闭包函数下的 ruleFunc 判断为 false, 拒绝执行此方法。");
                }
                $this->status = self::STATUS_RULE_FAILED;
                return false;
            }
            $this->store = $q_f(...$params);
            $this->status = self::STATUS_NORMAL;
            return true;
        }
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] 正在判断 " . $q_c . '::' . $q_f . ' 方法下的 ruleFunc ...');
        }
        if ($rule_func !== null && !$rule_func($v)) {
            if ($this->log) {
                Console::verbose("[事件分发{$this->eid}] " . $q_c . '::' . $q_f . ' 方法下的 ruleFunc 判断为 false, 拒绝执行此方法。');
            }
            $this->status = self::STATUS_RULE_FAILED;
            return false;
        }
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] " . $q_c . '::' . $q_f . ' 方法下的 ruleFunc 为真，继续执行方法本身 ...');
        }
        if (isset(EventManager::$middleware_map[$q_c][$q_f])) {
            $middlewares = EventManager::$middleware_map[$q_c][$q_f];
            if ($this->log) {
                Console::verbose("[事件分发{$this->eid}] " . $q_c . '::' . $q_f . ' 方法还绑定了 Middleware：' . implode(', ', array_map(function ($x) { return $x->middleware; }, $middlewares)));
            }
            $before_result = true;
            $r = [];
            foreach ($middlewares as $k => $middleware) {
                if (!isset(EventManager::$middlewares[$middleware->middleware])) {
                    if ((ZMConfig::get('global', 'runtime')['middleware_error_policy'] ?? 1) == 1) {
                        throw new AnnotationException("Annotation parse error: Unknown MiddlewareClass named \"{$middleware->middleware}\"!");
                    }
                    continue;
                }
                $middleware_obj = EventManager::$middlewares[$middleware->middleware];
                $before = $middleware_obj['class'];
                // var_dump($middleware_obj);
                $r[$k] = new $before();
                $r[$k]->class = $q_c;
                $r[$k]->method = $q_f;
                $r[$k]->middleware = $middleware;
                $r[$k]->current_event = $v;
                if (isset($middleware_obj['before'])) {
                    if ($this->log) {
                        Console::verbose("[事件分发{$this->eid}] Middleware 存在前置事件，执行中 ...");
                    }
                    $rs = $middleware_obj['before'];
                    $before_result = $r[$k]->{$rs}(...$params);
                    if ($before_result === false) {
                        if ($this->log) {
                            Console::verbose("[事件分发{$this->eid}] Middleware 前置事件为 false，停止执行原事件，开始执行下一事件。");
                        }
                        break;
                    }
                    if ($this->log) {
                        Console::verbose("[事件分发{$this->eid}] Middleware 前置事件为 true，继续执行原事件。");
                    }
                }
            }
            if ($before_result) {
                try {
                    $q_o = ZMUtil::getModInstance($q_c);
                    $q_o->_running_annotation = $v;
                    if ($this->log) {
                        Console::verbose("[事件分发{$this->eid}] 正在执行方法 " . $q_c . '::' . $q_f . ' ...');
                    }
                    $this->store = $q_o->{$q_f}(...$params);
                } catch (Exception $e) {
                    if ($e instanceof InterruptException) {
                        if ($this->log) {
                            Console::verbose("[事件分发{$this->eid}] 检测到事件阻断调用，正在跳出事件分发器 ...");
                        }
                        throw $e;
                    }
                    if ($this->log) {
                        Console::verbose("[事件分发{$this->eid}] 方法 " . $q_c . '::' . $q_f . ' 执行过程中抛出了异常，正在倒序查找 Middleware 中的捕获方法 ...');
                    }
                    for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                        $middleware_obj = EventManager::$middlewares[$middlewares[$i]->middleware];
                        if (!isset($middleware_obj['exceptions'])) {
                            continue;
                        }
                        foreach ($middleware_obj['exceptions'] as $name => $method) {
                            if ($e instanceof $name) {
                                if ($this->log) {
                                    Console::verbose("[事件分发{$this->eid}] 方法 " . $q_c . '::' . $q_f . ' 的异常 ' . get_class($e) . ' 被 Middleware:' . $middlewares[$i] . ' 下的 ' . get_class($r[$i]) . '::' . $method . ' 捕获。');
                                }
                                $r[$i]->{$method}($e);
                                self::interrupt();
                            }
                        }
                    }
                    throw $e;
                }
                $cnts = count($middlewares) - 1;
                for ($i = $cnts; $i >= 0; --$i) {
                    $middleware_obj = EventManager::$middlewares[$middlewares[$i]->middleware];
                    if (isset($middleware_obj['after'], $r[$i])) {
                        if ($this->log) {
                            Console::verbose("[事件分发{$this->eid}] Middleware 存在后置事件，执行中 ...");
                        }
                        $r[$i]->{$middleware_obj['after']}(...$params);
                        if ($this->log) {
                            Console::verbose("[事件分发{$this->eid}] Middleware 后置事件执行完毕！");
                        }
                    }
                }
                $this->status = self::STATUS_NORMAL;
                return true;
            }
            $this->status = self::STATUS_BEFORE_FAILED;
            return false;
        }
        $q_o = ZMUtil::getModInstance($q_c);
        $q_o->_running_annotation = $v;
        if ($this->log) {
            Console::verbose("[事件分发{$this->eid}] 正在执行方法 " . $q_c . '::' . $q_f . ' ...');
        }
        $this->store = $q_o->{$q_f}(...$params);
        $this->status = self::STATUS_NORMAL;
        return true;
    }

    public function getEid(): int
    {
        return $this->eid;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
