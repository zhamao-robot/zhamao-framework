<?php


namespace ZM;


use Exception;
use ZM\Command\DaemonReloadCommand;
use ZM\Command\DaemonStatusCommand;
use ZM\Command\DaemonStopCommand;
use ZM\Command\InitCommand;
use ZM\Command\PureHttpCommand;
use ZM\Command\RunServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Utils\DataProvider;

class ConsoleApplication extends Application
{
    const VERSION_ID = 386;
    const VERSION = "2.3.0-beta1";

    public function __construct(string $name = 'UNKNOWN') {
        define("ZM_VERSION_ID", self::VERSION_ID);
        define("ZM_VERSION", self::VERSION);
        parent::__construct($name, ZM_VERSION);
    }

    public function initEnv() {
        $this->selfCheck();

        if (!is_dir(__DIR__ . '/../../vendor')) {
            define("LOAD_MODE", 1); // composer项目模式
            define("LOAD_MODE_COMPOSER_PATH", getcwd());
        } else  {
            define("LOAD_MODE", 0); // 源码模式
        }

        //if (LOAD_MODE === 0) $this->add(new BuildCommand()); //只有在git源码模式才能使用打包指令
        if (LOAD_MODE === 0) define("WORKING_DIR", getcwd());
        elseif (LOAD_MODE == 1) define("WORKING_DIR", realpath(__DIR__ . "/../../"));
        if (file_exists(DataProvider::getWorkingDir() . "/vendor/autoload.php")) {
            /** @noinspection PhpIncludeInspection */
            require_once DataProvider::getWorkingDir() . "/vendor/autoload.php";
        }
        if (LOAD_MODE == 0) {
            $composer = json_decode(file_get_contents(DataProvider::getWorkingDir() . "/composer.json"), true);
            if (!isset($composer["autoload"]["psr-4"]["Module\\"])) {
                echo "框架源码模式需要在autoload文件中添加Module目录为自动加载，是否添加？[Y/n] ";
                $r = strtolower(trim(fgets(STDIN)));
                if ($r === "" || $r === "y") {
                    $composer["autoload"]["psr-4"]["Module\\"] = "src/Module";
                    $composer["autoload"]["psr-4"]["Custom\\"] = "src/Custom";
                    $r = file_put_contents(DataProvider::getWorkingDir() . "/composer.json", json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    if ($r !== false) {
                        echo "成功添加！请运行 composer dump-autoload ！\n";
                        exit(1);
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
            new PureHttpCommand() //纯HTTP服务器指令
        ]);
        /*
        $command_register = ZMConfig::get("global", "command_register_class") ?? [];
        foreach ($command_register as $v) {
            $obj = new $v();
            if (!($obj instanceof Command)) throw new TypeError("Command register class must be extended by Symfony\\Component\\Console\\Command\\Command");
            $this->add($obj);
        }*/
        return $this;
    }

    /**
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null) {
        try {
            return parent::run($input, $output);
        } catch (Exception $e) {
            die("{$e->getMessage()} at {$e->getFile()}({$e->getLine()})");
        }
    }

    private function selfCheck() {
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/19\n");
        if (version_compare(SWOOLE_VERSION, "4.4.13") == -1) die("You must install swoole version >= 4.4.13 !");
        if (version_compare(PHP_VERSION, "7.2") == -1) die("PHP >= 7.2 required.");
        return true;
    }
}
