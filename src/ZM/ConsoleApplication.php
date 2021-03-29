<?php


namespace ZM;


use Exception;
use ZM\Command\CheckConfigCommand;
use ZM\Command\DaemonReloadCommand;
use ZM\Command\DaemonStatusCommand;
use ZM\Command\DaemonStopCommand;
use ZM\Command\InitCommand;
use ZM\Command\PureHttpCommand;
use ZM\Command\RunServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Command\SystemdCommand;

class ConsoleApplication extends Application
{
    const VERSION_ID = 404;
    const VERSION = "2.4.4";

    public function __construct(string $name = 'UNKNOWN') {
        define("ZM_VERSION_ID", self::VERSION_ID);
        define("ZM_VERSION", self::VERSION);
        parent::__construct($name, ZM_VERSION);
    }

    public function initEnv($with_default_cmd = ""): ConsoleApplication {
        $this->selfCheck();

        define("WORKING_DIR", getcwd());
        define("LOAD_MODE", is_dir(WORKING_DIR . "/src/ZM") ? 0 : 1);
        define("FRAMEWORK_ROOT_DIR", realpath(__DIR__ . "/../../"));
        if (LOAD_MODE == 0) {
            $composer = json_decode(file_get_contents(WORKING_DIR . "/composer.json"), true);
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
                        echo "添加失败！请按任意键继续！";
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
            new InitCommand(), //初始化用的，用于项目初始化和phar初始化
            new PureHttpCommand(), //纯HTTP服务器指令
            new SystemdCommand()
        ]);
        if (LOAD_MODE === 1) {
            $this->add(new CheckConfigCommand());
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
            die("{$e->getMessage()} at {$e->getFile()}({$e->getLine()})");
        }
    }

    private function selfCheck(): bool {
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/19\n");
        if (version_compare(SWOOLE_VERSION, "4.5.0") == -1) die("You must install swoole version >= 4.5.0 !");
        if (version_compare(PHP_VERSION, "7.2") == -1) die("PHP >= 7.2 required.");
        return true;
    }
}
