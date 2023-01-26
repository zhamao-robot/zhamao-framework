<?php

declare(strict_types=1);

namespace ZM;

use Exception;
use Phar;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Command\BuildCommand;
use ZM\Command\CheckConfigCommand;
use ZM\Command\Daemon\DaemonReloadCommand;
use ZM\Command\Daemon\DaemonStatusCommand;
use ZM\Command\Daemon\DaemonStopCommand;
use ZM\Command\Generate\SystemdGenerateCommand;
use ZM\Command\InitCommand;
use ZM\Command\Module\ModuleListCommand;
use ZM\Command\Module\ModulePackCommand;
use ZM\Command\Module\ModuleUnpackCommand;
use ZM\Command\PureHttpCommand;
use ZM\Command\RunServerCommand;
use ZM\Command\Server\ServerReloadCommand;
use ZM\Command\Server\ServerStatusCommand;
use ZM\Command\Server\ServerStopCommand;
use ZM\Exception\InitException;

class ConsoleApplication extends Application
{
    public const VERSION_ID = 480;

    public const VERSION = '2.8.6';

    private static $obj;

    /**
     * @throws InitException
     */
    public function __construct(string $name = 'UNKNOWN')
    {
        if (self::$obj !== null) {
            throw new InitException(zm_internal_errcode('E00069') . 'Initializing another Application is not allowed!');
        }
        define('ZM_VERSION_ID', self::VERSION_ID);
        define('ZM_VERSION', self::VERSION);
        self::$obj = $this;
        parent::__construct($name, ZM_VERSION);
    }

    /**
     * @throws InitException
     */
    public function initEnv(string $with_default_cmd = ''): ConsoleApplication
    {
        if (defined('WORKING_DIR')) {
            throw new InitException();
        }

        _zm_env_check();

        // 定义多进程的全局变量
        define('ZM_PROCESS_MASTER', 1);
        define('ZM_PROCESS_MANAGER', 2);
        define('ZM_PROCESS_WORKER', 4);
        define('ZM_PROCESS_USER', 8);
        define('ZM_PROCESS_TASKWORKER', 16);

        define('WORKING_DIR', getcwd());

        if (!is_dir(_zm_pid_dir())) {
            @mkdir(_zm_pid_dir());
        }

        if (Phar::running() !== '') {
            echo "* Running in phar mode.\n";
            define('SOURCE_ROOT_DIR', Phar::running());
            define('LOAD_MODE', is_dir(SOURCE_ROOT_DIR . '/src/ZM') ? 0 : 1);
            define('FRAMEWORK_ROOT_DIR', LOAD_MODE == 1 ? (SOURCE_ROOT_DIR . '/vendor/zhamao/framework') : SOURCE_ROOT_DIR);
        } else {
            define('SOURCE_ROOT_DIR', WORKING_DIR);
            define('LOAD_MODE', is_dir(SOURCE_ROOT_DIR . '/src/ZM') ? 0 : 1);
            define('FRAMEWORK_ROOT_DIR', realpath(__DIR__ . '/../../'));
        }

        $this->addCommands([
            new DaemonStatusCommand(),
            new DaemonReloadCommand(),
            new DaemonStopCommand(),
            new RunServerCommand(), // 运行主服务的指令控制器
            new ServerStatusCommand(),
            new ServerStopCommand(),
            new ServerReloadCommand(),
            new PureHttpCommand(), // 纯HTTP服务器指令
            new SystemdGenerateCommand(),
        ]);
        if (LOAD_MODE === 1) {
            $this->add(new CheckConfigCommand());
        }
        if (Phar::running() === '') {
            $this->add(new BuildCommand());
            $this->add(new InitCommand());
            $this->add(new ModulePackCommand());
            $this->add(new ModuleListCommand());
            $this->add(new ModuleUnpackCommand());
        }
        if (!empty($with_default_cmd)) {
            $this->setDefaultCommand($with_default_cmd);
        }
        return $this;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        try {
            return parent::run($input, $output);
        } catch (Exception $e) {
            echo zm_internal_errcode('E00005') . "{$e->getMessage()} at {$e->getFile()}({$e->getLine()})" . PHP_EOL;
            exit(1);
        }
    }
}
