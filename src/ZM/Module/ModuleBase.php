<?php

declare(strict_types=1);

namespace ZM\Module;

/**
 * @since 2.6
 */
abstract class ModuleBase
{
    /** @var string 模块名称 */
    protected $module_name;

    /** @var array 事件列表 */
    protected $events = [];

    /**
     * @param string $module_name 模块名称
     */
    public function __construct(string $module_name)
    {
        $this->module_name = $module_name;
    }

    /**
     * 获取模块名称
     * @return string
     */
    public function getModuleName()
    {
        return $this->module_name;
    }

    /**
     * 获取事件列表
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
