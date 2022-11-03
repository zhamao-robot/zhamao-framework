<?php

declare(strict_types=1);

namespace ZM\Annotation;

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

    /**
     * 注解调用器构造函数
     *
     * @param string $annotation_class 注解类名
     */
    public function __construct(string $annotation_class)
    {
        $this->annotation_class = $annotation_class;
        logger()->debug('开始分发注解 {annotation}', ['annotation' => $annotation_class]);
    }

    /**
     * 立刻中断注解调用器执行
     *
     * @param  mixed              $return_var 中断执行返回值，传入null则代表无返回值
     * @throws InterruptException
     */
    public static function interrupt($return_var = null)
    {
        throw new InterruptException($return_var);
    }

    /**
     * 设置执行前判断注解是否应该被执行的检查回调函数
     *
     * @param  callable $rule 回调函数
     * @return $this
     */
    public function setRuleCallback(callable $rule): AnnotationHandler
    {
        logger()->debug('注解调用器设置事件ruleFunc: {annotation}', ['annotation' => $this->annotation_class]);
        $this->rule_callback = $rule;
        return $this;
    }

    /**
     * 设置成功执行后有返回值时执行的返回值后续逻辑回调函数
     *
     * @param  callable $return 回调函数
     * @return $this
     */
    public function setReturnCallback(callable $return): AnnotationHandler
    {
        logger()->debug('注解调用器设置事件returnFunc: {annotation}', ['annotation' => $this->annotation_class]);
        $this->return_callback = $return;
        return $this;
    }

    /**
     * 调用注册了该注解的所有函数们
     * 此处会遍历所有注册了当前注解的函数，并支持中间件插入
     *
     * @param  mixed      ...$params 传入的参数们
     * @throws \Throwable
     */
    public function handleAll(...$params)
    {
        try {
            // 遍历注册的注解
            foreach ((AnnotationMap::$_list[$this->annotation_class] ?? []) as $v) {
                // 调用单个注解
                $this->handle($v, $this->rule_callback, ...$params);
                // 执行完毕后检查状态，如果状态是规则判断或中间件before不通过，则重置状态后继续执行别的注解函数
                if ($this->status == self::STATUS_BEFORE_FAILED || $this->status == self::STATUS_RULE_FAILED) {
                    $this->status = self::STATUS_NORMAL;
                    continue;
                }
                // 如果执行完毕，且设置了返回值后续逻辑的回调函数，那么就调用返回值回调的逻辑
                if (is_callable($this->return_callback) && $this->status === self::STATUS_NORMAL) {
                    ($this->return_callback)($this->return_val);
                }
            }
        } catch (InterruptException $e) {
            // InterruptException 用于中断，这里必须 catch，并标记状态
            $this->return_val = $e->return_var;
            $this->status = self::STATUS_INTERRUPTED;
        } catch (\Throwable $e) {
            // 其他类型的异常就顺势再抛出到外层，此层不做处理
            $this->status = self::STATUS_EXCEPTION;
            throw $e;
        }
    }

    /**
     * 调用单个注解
     *
     * @throws InterruptException
     * @throws \Throwable
     */
    public function handle(AnnotationBase $v, ?callable $rule_callback = null, ...$args): bool
    {
        // 由于3.0有额外的插件模式支持，所以注解就不再提供独立的闭包函数调用支持了
        // 提取要调用的目标类和方法名称
        $class = $v->class;
        $target_class = new $class();
        $target_method = $v->method;
        // 先执行规则，失败就返回false
        if ($rule_callback !== null && !$rule_callback($v)) {
            $this->status = self::STATUS_RULE_FAILED;
            return false;
        }
        $callback = [$target_class, $target_method];
        try {
            $this->return_val = middleware()->process($callback, ...$args);
        } catch (InterruptException $e) {
            // 这里直接抛出这个异常的目的就是给上层handleAll()捕获
            throw $e;
        } catch (\Throwable $e) {
            // 其余的异常就交给中间件的异常捕获器过一遍，没捕获的则继续抛出
            $this->status = self::STATUS_EXCEPTION;
            throw $e;
        }
        return true;
    }

    /**
     * 获取分发的状态
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * 获取运行的返回值
     *
     * @return mixed
     */
    public function getReturnVal()
    {
        return $this->return_val;
    }
}
