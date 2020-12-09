<?php


namespace Framework;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use Swoole\Runtime;
use ZM\Annotation\Swoole\OnEvent;
use Exception;
use Swoole\WebSocket\Server;

/**
 * Class FrameworkLoader
 * Everything is beginning from here
 * @package Framework
 */
class FrameworkLoader
{
    /** @var GlobalConfig */
    public static $settings;

    /** @var FrameworkLoader|null */
    public static $instance = null;

    /**  @var float|string */
    public static $run_time;
    /**
     * @var array
     */
    public static $argv;

    /** @var Server */
    private $server;

    public function __construct($args = [])
    {
        $this->requireGlobalFunctions();
        if (LOAD_MODE == 0) define("WORKING_DIR", getcwd());
        elseif (LOAD_MODE == 1) define("WORKING_DIR", realpath(__DIR__ . "/../../"));
        elseif (LOAD_MODE == 2) echo "Phar mode: " . WORKING_DIR . PHP_EOL;
        //$this->registerAutoloader('classLoader');
        require_once "DataProvider.php";
        if (file_exists(DataProvider::getWorkingDir() . "/vendor/autoload.php")) {
            /** @noinspection PhpIncludeInspection */
            require_once DataProvider::getWorkingDir() . "/vendor/autoload.php";
        }
        if (LOAD_MODE == 0) {
            echo "* This is repository mode.\n";
            $composer = json_decode(file_get_contents(DataProvider::getWorkingDir() . "/composer.json"), true);
            if (!isset($composer["autoload"]["psr-4"]["Module\\"])) {
                echo "框架源码模式需要在autoload文件中添加Module目录为自动加载，是否添加？[Y/n] ";
                $r = strtolower(trim(fgets(STDIN)));
                if ($r === "" || $r === "y") {
                    $composer["autoload"]["psr-4"]["Module\\"] = "src/Module";
                    $composer["autoload"]["psr-4"]["Custom\\"] = "src/Custom";
                    $r = file_put_contents(DataProvider::getWorkingDir() . "/composer.json", json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    if ($r !== false) {
                        echo "成功添加！请重新进行 composer update ！\n";
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
        if (LOAD_MODE == 2) {
            require_once FRAMEWORK_DIR . "/vendor/autoload.php";
            spl_autoload_register('phar_classloader');
        }


        self::$settings = new GlobalConfig();
        if (self::$settings->get("debug_mode") === true) {
            $args[] = "--debug-mode";
            $args[] = "--disable-console-input";
        }
        self::$argv = $args;
        if (!in_array("--debug-mode", self::$argv)) {
            Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
        }
        self::$settings = new GlobalConfig();
        ZMBuf::$globals = self::$settings;
        if (!self::$settings->success) die("Failed to load global config. Please check config/global.php file");
        $this->defineProperties();

        //start swoole Framework
        $this->selfCheck();
        try {
            $this->server = new Server(self::$settings->get("host"), self::$settings->get("port"));
            $settings = self::$settings->get("swoole");
            if (in_array("--daemon", $args)) {
                $settings["daemonize"] = 1;
                Console::log("已启用守护进程，输出重定向到 " . $settings["log_file"]);
                self::$argv[] = "--disable-console-input";
            }
            $this->server->set($settings);
            $all_event_class = self::$settings->get("server_event_handler_class") ?? [];
            if (!in_array(ServerEventHandler::class, $all_event_class)) {
                $all_event_class[] = ServerEventHandler::class;
            }
            $event_list = [];
            foreach ($all_event_class as $v) {
                $reader = new AnnotationReader();
                $reflection_class = new ReflectionClass($v);
                $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $vs) {
                    $method_annotations = $reader->getMethodAnnotations($vs);
                    if ($method_annotations != []) {
                        $annotation = $method_annotations[0];
                        if ($annotation instanceof OnEvent) {
                            $annotation->class = $v;
                            $annotation->method = $vs->getName();
                            $event_list[strtolower($annotation->event)] = $annotation;
                        }
                    }
                }
            }
            foreach ($event_list as $k => $v) {
                $this->server->on($k, function (...$param) use ($v) {
                    $c = $v->class;
                    //echo $c.PHP_EOL;
                    $c = new $c();
                    call_user_func_array([$c, $v->method], $param);
                });
            }

            ZMBuf::initAtomic();
            if (in_array("--remote-shell", $args)) RemoteShell::listen($this->server, "127.0.0.1");
            if (in_array("--log-error", $args)) ZMBuf::$atomics["info_level"]->set(0);
            if (in_array("--log-warning", $args)) ZMBuf::$atomics["info_level"]->set(1);
            if (in_array("--log-info", $args)) ZMBuf::$atomics["info_level"]->set(2);
            if (in_array("--log-verbose", $args)) ZMBuf::$atomics["info_level"]->set(3);
            if (in_array("--log-debug", $args)) ZMBuf::$atomics["info_level"]->set(4);
            Console::log(
                "host: " . self::$settings->get("host") .
                ", port: " . self::$settings->get("port") .
                ", log_level: " . ZMBuf::$atomics["info_level"]->get() .
                ", version: " . ZM_VERSION .
                "\nworking_dir: " . DataProvider::getWorkingDir()
            );
            global $motd;
            if (!file_exists(DataProvider::getWorkingDir() . "/config/motd.txt")) {
                echo $motd;
            } else {
                echo file_get_contents(DataProvider::getWorkingDir() . "/config/motd.txt");
            }
            if (in_array("--debug-mode", self::$argv))
                Console::warning("You are in debug mode, do not use in production!");
            $this->server->start();
        } catch (Exception $e) {
            Console::error("Framework初始化出现错误，请检查！");
            Console::error($e->getMessage());
            die;
        }
    }

    private function requireGlobalFunctions()
    {
        require_once __DIR__ . '/global_functions.php';
    }

    private function defineProperties()
    {
        define("ZM_START_TIME", microtime(true));
        define("ZM_DATA", self::$settings->get("zm_data"));
        define("ZM_VERSION", json_decode(file_get_contents(__DIR__ . "/../../composer.json"), true)["version"] ?? "unknown");
        define("CONFIG_DIR", self::$settings->get("config_dir"));
        define("CRASH_DIR", self::$settings->get("crash_dir"));
        @mkdir(ZM_DATA);
        @mkdir(CONFIG_DIR);
        @mkdir(CRASH_DIR);
        define("ZM_MATCH_ALL", 0);
        define("ZM_MATCH_FIRST", 1);
        define("ZM_MATCH_NUMBER", 2);
        define("ZM_MATCH_SECOND", 3);
        define("ZM_BREAKPOINT", 'if(in_array("--debug-mode", \Framework\FrameworkLoader::$argv)) extract(\Psy\debug(get_defined_vars(), isset($this) ? $this : @get_called_class()));');
        define("BP", ZM_BREAKPOINT);
        define("ZM_DEFAULT_FETCH_MODE", self::$settings->get("sql_config")["sql_default_fetch_mode"] ?? 4);
    }

    private function selfCheck()
    {
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\n");
        if (version_compare(SWOOLE_VERSION, "4.4.13") == -1) die("You must install swoole version >= 4.4.13 !");
        //if (!extension_loaded("gd")) die("Can not find gd extension.\n");
        //if (!extension_loaded("sockets")) die("Can not find sockets extension.\n");
        if (!function_exists("ctype_alpha")) die("Can not find ctype extension.\n");
        if (!function_exists("mb_substr")) die("Can not find mbstring extension.\n");
        if (substr(PHP_VERSION, 0, 1) < "7") die("PHP >=7 required.\n");
        //if (!function_exists("curl_exec")) die("Can not find curl extension.\n");
        //if (!class_exists("ZipArchive")) die("Can not find Zip extension.\n");
        //if (!file_exists(CRASH_DIR . "last_error.log")) die("Can not find log file.\n");
        return true;
    }
}

global $motd;
$motd = <<<EOL
 ______                                 
|__  / |__   __ _ _ __ ___   __ _  ___  
  / /| '_ \ / _` | '_ ` _ \ / _` |/ _ \ 
 / /_| | | | (_| | | | | | | (_| | (_) |
/____|_| |_|\__,_|_| |_| |_|\__,_|\___/ 


EOL;

