<?php

declare(strict_types=1);

namespace ZM\Annotation;

abstract class AnnotationBase implements \IteratorAggregate, \Stringable
{
    /** @var array|\Closure|string 方法名或闭包 */
    public array|\Closure|string $method = '';

    public $class = '';

    public array $group = [];

    public function __toString(): string
    {
        $str = self::class . ': ';
        foreach ($this as $k => $v) {
            $str .= "\n\t" . $k . ' => ';
            if (is_string($v)) {
                $str .= "\"{$v}\"";
            } elseif (is_numeric($v)) {
                $str .= $v;
            } elseif (is_bool($v)) {
                $str .= ($v ? 'TRUE' : 'FALSE');
            } elseif (is_array($v)) {
                $str .= json_encode($v, JSON_UNESCAPED_UNICODE);
            } elseif ($v instanceof \Closure) {
                $str .= '@AnonymousFunction';
            } elseif (is_null($v)) {
                $str .= 'NULL';
            } else {
                $str .= '@Unknown';
            }
        }
        return $str;
    }

    /**
     * 在 InstantPlugin 下调用，设置回调或匿名函数
     */
    public function on(callable|\Closure|string $method): AnnotationBase
    {
        $this->method = $method;
        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this);
    }

    public function isInGroup(string $name): bool
    {
        return in_array($name, $this->group);
    }

    public function addGroup(string $name)
    {
        $this->group[] = $name;
    }

    public function getGroups(): array
    {
        return $this->group;
    }
}
