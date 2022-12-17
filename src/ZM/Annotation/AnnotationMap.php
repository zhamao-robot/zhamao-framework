<?php

declare(strict_types=1);

namespace ZM\Annotation;

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

    /**
     * 将Parser解析后的注解注册到全局的 AnnotationMap
     *
     * @param AnnotationParser $parser 注解解析器
     */
    public static function loadAnnotationByParser(AnnotationParser $parser): void
    {
        // 生成后加入到全局list中
        self::$_list = array_merge(self::$_list, $parser->generateAnnotationList());
        self::$_map = $parser->getAnnotationMap();
    }
}
