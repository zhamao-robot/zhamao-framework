<?php

declare(strict_types=1);

namespace ZM\Annotation;

use ZM\Annotation\Interfaces\Level;

/**
 * 注解全局存取位置
 */
class AnnotationMap
{
    /**
     * 存取注解对象的列表，key是注解类名，value是该注解对应的数组
     *
     * @var array<string, array<AnnotationBase>>
     * @internal
     */
    public static array $_list = [];

    /**
     * 存取注解对象的三维列表，key1是注解所在的类名，key2是注解所在的方法名，value是该方法标注的注解们（数组）
     *
     * @var array<string, array<string, array<AnnotationBase>>>
     * @internal
     */
    public static array $_map = [];

    public static function loadAnnotationList(array $list): void
    {
        self::$_list = array_merge_recursive(self::$_list, $list);
    }

    public static function loadAnnotationMap(array $map): void
    {
        self::$_map = array_merge_recursive(self::$_map, $map);
    }

    /**
     * 添加一个独立的注解到全局注解列表中
     *
     * @param AnnotationBase $annotation 注解对象
     */
    public static function addSingleAnnotation(AnnotationBase $annotation): void
    {
        self::$_list[get_class($annotation)][] = $annotation;
        if ($annotation->class !== '') {
            self::$_map[$annotation->class][$annotation->method][] = $annotation;
        }
    }

    /**
     * @return AnnotationBase[]
     */
    public static function getAnnotationList(string $class_name): array
    {
        return self::$_list[$class_name] ?? [];
    }

    /**
     * 排序所有的注解
     */
    public static function sortAnnotationList(): void
    {
        foreach (self::$_list as $class => $annotations) {
            if (is_a($class, Level::class, true)) {
                usort(self::$_list[$class], function ($a, $b) {
                    $left = $a->getLevel();     /** @phpstan-ignore-line */
                    $right = $b->getLevel();    /* @phpstan-ignore-line */
                    return $left > $right ? -1 : ($left == $right ? 0 : 1);
                });
            }
        }
    }
}
