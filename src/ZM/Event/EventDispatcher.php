<?php


namespace ZM\Event;


use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Exception\InterruptException;
use ZM\Utils\ZMUtil;

class EventDispatcher
{
    /** @var string */
    private $class;
    /** @var null|callable */
    private $rule = null;
    /** @var null|callable */
    private $return_func = null;

    /**
     * @throws InterruptException
     */
    public static function interrupt() {
        throw new InterruptException('interrupt');
    }

    public function __construct(string $class = '') {
        $this->class = $class;
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
                if ($result !== false && is_callable($this->return_func)) ($this->return_func)($result);
            }
            return true;
        } catch (InterruptException $e) {
            return null;
        } catch (AnnotationException $e) {
            return false;
        }
    }

    /**
     * @param AnnotationBase|null $v
     * @param null $rule_func
     * @param mixed ...$params
     * @return bool
     * @throws AnnotationException
     * @throws InterruptException
     */
    public function dispatchEvent(?AnnotationBase $v, $rule_func = null, ...$params) {
        $q_c = $v->class;
        $q_f = $v->method;
        if ($rule_func !== null && !$rule_func($v)) return false;
        if (isset(EventManager::$middleware_map[$q_c][$q_f])) {
            $middlewares = EventManager::$middleware_map[$q_c][$q_f];
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
                    $rs = $middleware_obj["before"];
                    $before_result = $r[$k]->$rs(...$params);
                    if ($before_result === false) break;
                }
            }
            if ($before_result) {
                try {
                    $q_o = ZMUtil::getModInstance($q_c);
                    $result = $q_o->$q_f(...$params);
                } catch (Exception $e) {
                    if ($e instanceof InterruptException) throw $e;
                    for ($i = count($middlewares) - 1; $i >= 0; --$i) {
                        $middleware_obj = EventManager::$middlewares[$middlewares[$i]];
                        if (!isset($middleware_obj["exceptions"])) continue;
                        foreach ($middleware_obj["exceptions"] as $name => $method) {
                            if ($e instanceof $name) {
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
                        $r[$i]->{$middleware_obj["after"]}(...$params);
                    }
                }
                return $result;
            }
            return false;
        } else {
            $q_o = ZMUtil::getModInstance($q_c);
            return $q_o->$q_f(...$params);
        }
    }
}
