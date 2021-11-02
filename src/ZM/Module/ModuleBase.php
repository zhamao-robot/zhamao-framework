<?php

namespace ZM\Module;

/**
 * @since 2.6
 */
abstract class ModuleBase
{
    protected $module_name;

    protected $events = [];

    public function __construct($module_name) {
        $this->module_name = $module_name;
    }

    /**
     * @return mixed
     */
    public function getModuleName() {
        return $this->module_name;
    }

    /**
     * @return array
     */
    public function getEvents(): array {
        return $this->events;
    }
}