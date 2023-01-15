<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\AnnotationParser;

trait PluginLoadTrait
{
    /** @var null|mixed 插件加载时的回调 */
    protected mixed $on_plugin_load = null;

    /**
     * 设置当前插件的插件加载前回调
     *
     * @param callable $callback 回调函数
     */
    public function onPluginLoad(callable $callback): void
    {
        $this->on_plugin_load = $callback;
    }

    /**
     * 调用插件加载前回调（需要在解析插件的注解时调用，并传入注解解析器）
     *
     * @param AnnotationParser $parser 注解解析器
     * @internal
     */
    public function emitPluginLoad(AnnotationParser $parser): void
    {
        if (is_callable($this->on_plugin_load)) {
            ($this->on_plugin_load)($parser);
        }
    }
}
