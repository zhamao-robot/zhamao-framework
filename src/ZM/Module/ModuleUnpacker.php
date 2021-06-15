<?php


namespace ZM\Module;


class ModuleUnpacker
{
    private $module;

    public function __construct(array $module) {
        $this->module = $module;
    }

    /**
     * 解包模块
     * @return array
     */
    public function unpack(): array {
        // TODO: 解包模块到src
        return $this->module;
    }
}