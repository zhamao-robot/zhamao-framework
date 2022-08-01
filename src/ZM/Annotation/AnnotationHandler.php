<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Generator;
use Throwable;
use ZM\Annotation\Middleware\Middleware;
use ZM\Exception\InterruptException;

/**
 * 注解调用器，原 EventDispatcher
 */
class AnnotationHandler
{
    public const STATUS_NORMAL = 0;            // 正常结束

    public const STATUS_INTERRUPTED = 1;       // 被interrupt了，不管在什么地方

    public const STATUS_EXCEPTION = 2;         // 执行过程中抛出了异常

    public const STATUS_BEFORE_FAILED = 3;     // 中间件HandleBefore返回了false，所以不执行此方法

    public const STATUS_RULE_FAILED = 4;       // 判断事件执行的规则函数判定为false，所以不执行此方法

    /** @var AnnotationBase|string */
    private $annotation_class;

    /** @var callable */
    private $rule_callback;

    /** @var callable */
    private $return_callback;

    /** @var int */
    private $status = self::STATUS_NORMAL;

    /** @var mixed */
    private $return_val;

    public function __construct(string $annotation_class)
    {
        $this->annotation_class = $annotation_class;
        logger()->debug('开始分发注解 {annotation}', ['annotation' => $annotation_class]);
    }

    public static function interrupt($return_var = null)
    {
        throw new InterruptException($return_var);
    }

    public function setRuleCallback(callable $rule): AnnotationHandler
    {
        logger()->debug('注解调用器设置事件ruleFunc: {annotation}', ['annotation' => $this->annotation_class]);
        $this->rule_callback = $rule;
        return $this;
    }

    public function setReturnCallback(callable $return): AnnotationHandler
    {
        logger()->debug('注解调用器设置事件returnFunc: {annotation}', ['annotation' => $this->annotation_class]);
        $this->return_callback = $return;
        return $this;
    }

    /**
     * @param  mixed     ...$params
     * @throws Throwable
     */
    public function handleAll(...$params)
    {
        try {
            foreach ((AnnotationMap::$_list[$this->annotation_class] ?? []) as $v) {
                $this->handle($v, $this->rule_callback, ...$params);
                if ($this->status == self::STATUS_BEFORE_FAILED || $this->status == self::STATUS_RULE_FAILED) {
                    $this->status = self::STATUS_NORMAL;
                    continue;
                }
                if (is_callable($this->return_callback) && $this->status === self::STATUS_NORMAL) {
                    ($this->return_callback)($this->return_val);
                }
            }
        } catch (InterruptException $e) {
            $this->return_val = $e->return_var;
            $this->status = self::STATUS_INTERRUPTED;
        } catch (Throwable $e) {
            $this->status = self::STATUS_EXCEPTION;
            throw $e;
        }
    }

    public function handle(AnnotationBase $v, ?callable $rule_callback = null, ...$params): bool
    {
        $target_class = resolve($v->class);
        $target_method = $v->method;
        // 先执行规则
        if ($rule_callback !== null && !$rule_callback($this, $params)) {
            $this->status = self::STATUS_RULE_FAILED;
            return false;
        }

        // 检查中间件
        $mid_obj = [];
        $before_result = true;
        foreach ($this->getRegisteredMiddlewares($target_class, $target_method) as $v) {
            $mid_obj[] = $v[0]; // 投喂中间件
            if ($v[1] !== '') { // 顺带执行before
                if (function_exists('container')) {
                    $before_result = container()->call([$v[0], $v[1]], $params);
                } else {
                    $before_result = call_user_func([$v[0], $v[1]], $params);
                }
                if ($before_result === false) {
                    break;
                }
            }
        }
        $mid_obj_cnt1 = count($mid_obj) - 1;
        if ($before_result) { // before全部通过了
            try {
                // 执行注解绑定的方法
                // TODO: 记得完善好容器后把这里的这个if else去掉
                if (function_exists('container')) {
                    $this->return_val = container()->call([$target_class, $target_method], $params);
                } else {
                    $this->return_val = call_user_func([$target_class, $target_method], $params);
                }
            } catch (Throwable $e) {
                if ($e instanceof InterruptException) {
                    throw $e;
                }
                for ($i = $mid_obj_cnt1; $i >= 0; --$i) {
                    $obj = $mid_obj[$i];
                    foreach ($obj[3] as $name => $method) {
                        if ($e instanceof $name) {
                            $obj[0]->{$method}($e);
                            return false;
                        }
                    }
                }
                throw $e;
            }
        } else {
            $this->status = self::STATUS_BEFORE_FAILED;
        }
        for ($i = $mid_obj_cnt1; $i >= 0; --$i) {
            if ($mid_obj[$i][2] !== '') {
                $mid_obj[$i][0]->{$mid_obj[$i][2]}($this->return_val);
            }
        }
        return true;
    }

    /**
     * 获取注册过的中间件
     *
     * @param object|string $class  类对象
     * @param string        $method 方法名称
     */
    private function getRegisteredMiddlewares($class, string $method): Generator
    {
        foreach (AnnotationMap::$_map[get_class($class)][$method] ?? [] as $annotation) {
            if ($annotation instanceof Middleware) {
                $name = $annotation->name;
                $reg_mid = AnnotationMap::$_middleware_map[$name]['class'] ?? null;
                if ($reg_mid === null) {
                    logger()->error('Not a valid middleware name: {name}', ['name' => $name]);
                    continue;
                }

                $obj = new $reg_mid($annotation->params);
                yield [
                    $obj,
                    AnnotationMap::$_middleware_map[$name]['before'] ?? '',
                    AnnotationMap::$_middleware_map[$name]['after'] ?? '',
                    AnnotationMap::$_middleware_map[$name]['exceptions'] ?? [],
                ];
            }
        }
        return [];
    }
}
