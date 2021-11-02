<?php

namespace ZM;

use ZM\Command\RunServerCommand;
use ZM\Console\Console;
use ZM\Event\EventManager;
use ZM\Exception\InitException;
use ZM\Module\ModuleBase;

/**
 * @since 2.6
 */
class ZMServer
{
    protected $app_name;

    /** @var ModuleBase[] */
    protected $modules = [];

    public function __construct($app_name) {
        $this->app_name = $app_name;
    }

    public function addModule($module_class) {
        $this->modules[] = $module_class;
    }

    public function run() {
        Console::setLevel(4);
        foreach ($this->modules as $module_class) {
            foreach ($module_class->getEvents() as $event) {
                EventManager::addEvent(get_class($event), $event);
            }
        }
        echo "Running...\n";
        if (defined("WORKDING_DIR")) throw new InitException();

        _zm_env_check();

        define("WORKING_DIR", getcwd());
        define("SOURCE_ROOT_DIR", WORKING_DIR);
        define("LOAD_MODE", is_dir(SOURCE_ROOT_DIR . "/src/ZM") ? 0 : 1);
        define("FRAMEWORK_ROOT_DIR", realpath(__DIR__ . "/../../"));
        define("ZM_VERSION_ID", ConsoleApplication::VERSION_ID);
        define("ZM_VERSION", ConsoleApplication::VERSION);
        $options = array_map(function ($x) {
            return $x->getDefault();
        }, RunServerCommand::exportDefinition()->getOptions());
        (new Framework($options, true))->start();
    }

    /**
     * @return mixed
     */
    public function getAppName() {
        return $this->app_name;
    }
}