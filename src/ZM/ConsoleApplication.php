<?php


namespace ZM;


use Exception;
use Phar;
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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Console\Console;
use ZM\Exception\InitException;

class ConsoleApplication extends Application
{
    private static $obj = null;

    const VERSION_ID = 411;
    const VERSION = "2.5.0-b3";

    /**
     * @throws InitException
     */
    public function __construct(string $name = 'UNKNOWN') {
        if (self::$obj !== null) throw new InitException(zm_internal_errcode("E00069") . "Initializing another Application is not allowed!");
        define("ZM_VERSION_ID", self::VERSION_ID);
        define("ZM_VERSION", self::VERSION);
        self::$obj = $this;
        parent::__construct($name, ZM_VERSION);
    }

    /**
     * @throws InitException
     */
    public function initEnv($with_default_cmd = ""): ConsoleApplication {
        if (defined("WORKDING_DIR")) throw new InitException();
        $this->selfCheck();

        define("WORKING_DIR", getcwd());
        if (Phar::running() !== "") {
            echo "* Running in phar mode.\n";
            define("SOURCE_ROOT_DIR", Phar::running());
            define("LOAD_MODE", is_dir(SOURCE_ROOT_DIR . "/src/ZM") ? 0 : 1);
            define("FRAMEWORK_ROOT_DIR", LOAD_MODE == 1 ? (SOURCE_ROOT_DIR . "/vendor/zhamao/framework") : SOURCE_ROOT_DIR);
        } else {
            define("SOURCE_ROOT_DIR", WORKING_DIR);
            define("LOAD_MODE", is_dir(SOURCE_ROOT_DIR . "/src/ZM") ? 0 : 1);
            define("FRAMEWORK_ROOT_DIR", realpath(__DIR__ . "/../../"));
        }
        if (LOAD_MODE == 0) {
            $composer = json_decode(file_get_contents(SOURCE_ROOT_DIR . "/composer.json"), true);
            if (!isset($composer["autoload"]["psr-4"]["Module\\"])) {
                echo "框架源码模式需要在autoload文件中添加Module目录为自动加载，是否添加？[Y/n] ";
                $r = strtolower(trim(fgets(STDIN)));
                if ($r === "" || $r === "y") {
                    $composer["autoload"]["psr-4"]["Module\\"] = "src/Module";
                    $composer["autoload"]["psr-4"]["Custom\\"] = "src/Custom";
                    $r = file_put_contents(WORKING_DIR . "/composer.json", json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    if ($r !== false) {
                        echo "成功添加！请运行 composer dump-autoload ！\n";
                        exit(0);
                    } else {
                        echo zm_internal_errcode("E00006") . "添加失败！请按任意键继续！";
                        fgets(STDIN);
                        exit(1);
                    }
                } else {
                    exit(1);
                }
            }
        }

        $this->addCommands([
            new DaemonStatusCommand(),
            new DaemonReloadCommand(),
            new DaemonStopCommand(),
            new RunServerCommand(), //运行主服务的指令控制器
            new PureHttpCommand(), //纯HTTP服务器指令
            new SystemdGenerateCommand()
        ]);
        if (LOAD_MODE === 1) {
            $this->add(new CheckConfigCommand());
        }
        if (Phar::running() === "") {
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

    /**
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int {
        try {
            return parent::run($input, $output);
        } catch (Exception $e) {
            die(zm_internal_errcode("E00005") . "{$e->getMessage()} at {$e->getFile()}({$e->getLine()})");
        }
    }

    /**
     * 启动炸毛前要做的环境检查项目
     */
    private function selfCheck(): void {
        if (!extension_loaded("swoole")) die(zm_internal_errcode("E00001") . "Can not find swoole extension.\n");
        if (version_compare(SWOOLE_VERSION, "4.5.0") == -1) die(zm_internal_errcode("E00002") . "You must install swoole version >= 4.5.0 !");
        if (version_compare(PHP_VERSION, "7.2") == -1) die(zm_internal_errcode("E00003") . "PHP >= 7.2 required.");
        if (version_compare(SWOOLE_VERSION, "4.6.7") < 0 && !extension_loaded("pcntl")) {
            Console::error(zm_internal_errcode("E00004") . "Swoole 版本必须不低于 4.6.7 或 PHP 安装加载了 pcntl 扩展！");
            die();
        }
    }
}
