<?php

declare(strict_types=1);

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
    /** @var string App名称 */
    protected $app_name;

    /** @var ModuleBase[] */
    protected $modules = [];

    /**
     * @param string $app_name App名称
     */
    public function __construct(string $app_name)
    {
        $this->app_name = $app_name;
    }

    /**
     * @param mixed $module_class
     */
    public function addModule($module_class)
    {
        $this->modules[] = $module_class;
    }

    /**
     * @throws InitException
     */
    public function run()
    {
        Console::setLevel(4);
        foreach ($this->modules as $module_class) {
            foreach ($module_class->getEvents() as $event) {
                EventManager::addEvent(get_class($event), $event);
            }
        }
        echo "Running...\n";
        if (defined('WORKING_DIR')) {
            throw new InitException();
        }

        _zm_env_check();

        define('WORKING_DIR', getcwd());
        define('SOURCE_ROOT_DIR', WORKING_DIR);
        define('LOAD_MODE', is_dir(SOURCE_ROOT_DIR . '/src/ZM') ? 0 : 1);
        define('FRAMEWORK_ROOT_DIR', realpath(__DIR__ . '/../../'));
        define('ZM_VERSION_ID', ConsoleApplication::VERSION_ID);
        define('ZM_VERSION', ConsoleApplication::VERSION);
        $options = array_map(function ($x) {
            return $x->getDefault();
        }, RunServerCommand::exportDefinition()->getOptions());
        (new Framework($options, true))->start();
    }

    public function getAppName(): string
    {
        return $this->app_name;
    }
}
