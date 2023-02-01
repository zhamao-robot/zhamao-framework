<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

trait PluginPackTrait
{
    protected mixed $on_pack = null;

    protected mixed $on_pack_filter = null;

    /**
     * 设置插件被打包前执行的回调函数
     *
     * @param callable $callback 回调函数
     */
    public function onPack(callable $callback): void
    {
        $this->on_pack = $callback;
    }

    public function filterPack(callable $callback): void
    {
        $this->on_pack_filter = $callback;
    }

    /**
     * @internal
     */
    public function emitPack(): void
    {
        if (is_callable($this->on_pack)) {
            ($this->on_pack)();
        }
    }

    /**
     * @internal
     * @param  string $file 文件名称，由 PluginManager 传入
     * @return bool   返回 False 代表该文件不需要打包
     */
    public function emitFilterPack(string $file): bool
    {
        if (is_callable($this->on_pack_filter)) {
            return (bool) ($this->on_pack_filter)($file);
        }
        return true;
    }
}
