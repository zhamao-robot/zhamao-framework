<?php

declare(strict_types=1);

namespace ZM\Annotation;

use Closure;

abstract class AnnotationBase
{
    public $method;

    public $class;

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
}
