<?php

declare(strict_types=1);

namespace ZM\Annotation;

/**
 * 注解全局存取位置
 */
class AnnotationMap
{
    /**
     * 存取注解对象的列表
     *
     * @var array<string, array<AnnotationBase>>
     * @internal
     */
    public static $_list = [];

    /**
     * @var array<string, array<string, array<AnnotationBase>>>
     * @internal
     */
    public static $_map = [];

    /**
     * @var array
     * @internal
     */
    public static $_middleware_map = [];
}
