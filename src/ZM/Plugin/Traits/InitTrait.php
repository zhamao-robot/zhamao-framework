<?php

declare(strict_types=1);

namespace ZM\Plugin\Traits;

use ZM\Annotation\Framework\Init;

trait InitTrait
{
    /** @var Init[] 插件启动回调 */
    protected array $on_init = [];

    /**
     * 设置当前插件的加载后的初始化回调
     *
     * @param callable $callback  回调函数
     * @param int      $worker_id 所在的 Worker 进程，默认在 #0
     * @param int      $level     优先级
     */
    public function onInit(callable $callback, int $worker_id = 0, int $level = 20): void
    {
        $init = new Init($worker_id, $level);
        $init->on($callback);
        $this->on_init[] = $init;
    }

    /**
     * 获取初始化注解事件回调
     *
     * @internal
     */
    public function getInits(): array
    {
        return $this->on_init;
    }
}
