<?php


namespace ZM;


use Exception;
use Symfony\Component\Console\Command\Command;
use TypeError;
use ZM\Command\InitCommand;
use ZM\Command\PureHttpCommand;
use ZM\Command\RunServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Config\ZMConfig;
use ZM\Utils\DataProvider;

class ConsoleApplication extends Application
{
    public function __construct(string $name = 'UNKNOWN') {
        $version = json_decode(file_get_contents(__DIR__ . "/../../composer.json"), true)["version"] ?? "UNKNOWN";
        parent::__construct($name, $version);
    }

    public function initEnv() {
        $this->selfCheck();
        $this->addCommands([
            new RunServerCommand(), //运行主服务的指令控制器
            new InitCommand(), //初始化用的，用于项目初始化和phar初始化
            new PureHttpCommand()
        ]);
        //if (LOAD_MODE === 0) $this->add(new BuildCommand()); //只有在git源码模式才能使用打包指令

        if (LOAD_MODE === 0) define("WORKING_DIR", getcwd());
        elseif (LOAD_MODE == 1) define("WORKING_DIR", realpath(__DIR__ . "/../../"));
        elseif (LOAD_MODE == 2) echo "Phar mode: " . WORKING_DIR . PHP_EOL;
        if (file_exists(DataProvider::getWorkingDir() . "/vendor/autoload.php")) {
            /** @noinspection PhpIncludeInspection */
            require_once DataProvider::getWorkingDir() . "/vendor/autoload.php";
        }
        if (LOAD_MODE == 2) {
            // Phar 模式，2.0 不提供哦
            //require_once FRAMEWORK_DIR . "/vendor/autoload.php";
            spl_autoload_register('phar_classloader');
        } elseif (LOAD_MODE == 0) {
            /** @noinspection PhpIncludeInspection
             * @noinspection RedundantSuppression
             */
            require_once WORKING_DIR . "/vendor/autoload.php";
        }

        if (!is_dir(DataProvider::getWorkingDir() . '/src/')) {
            die("Unable to find source directory.\nMaybe you need to run \"init\"?");
        }
        ZMConfig::setDirectory(DataProvider::getWorkingDir().'/config');
        ZMConfig::env($args["env"] ?? "");
        if(ZMConfig::get("global") === false) die("Global config load failed: ".ZMConfig::$last_error);

        $command_register = ZMConfig::get("global", "command_register_class") ?? [];
        foreach($command_register as $v) {
            $obj = new $v();
            if(!($obj instanceof Command)) throw new TypeError("Command register class must be extended by Symfony\\Component\\Console\\Command\\Command");
            $this->add($obj);
        }
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
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\n");
        if (version_compare(SWOOLE_VERSION, "4.4.13") == -1) die("You must install swoole version >= 4.4.13 !");
        //if (!extension_loaded("gd")) die("Can not find gd extension.\n");
        if (!extension_loaded("sockets")) die("Can not find sockets extension.\n");
        if (substr(PHP_VERSION, 0, 1) != "7") die("PHP >=7 required.\n");
        //if (!function_exists("curl_exec")) die("Can not find curl extension.\n");
        //if (!class_exists("ZipArchive")) die("Can not find Zip extension.\n");
        //if (!file_exists(CRASH_DIR . "last_error.log")) die("Can not find log file.\n");
        return true;
    }
}
