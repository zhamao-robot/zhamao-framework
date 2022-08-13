<?php

declare(strict_types=1);

namespace ZM\Annotation;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Traversable;

abstract class AnnotationBase implements IteratorAggregate
{
    public $method = '';

    public $class = '';

    public function __toString()
    {
        $str = __CLASS__ . ': ';
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
            } elseif ($v instanceof Closure) {
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
     *
     * @param Closure|string $method
     */
    public function withMethod($method): AnnotationBase
    {
        $this->method = $method;
        return $this;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this);
    }
}
