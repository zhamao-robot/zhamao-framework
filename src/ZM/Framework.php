<?php


namespace ZM;


use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Event\ServerEventHandler;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use Framework\RemoteShell;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Swoole\Runtime;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\OnEvent;
use ZM\Console\Console;

class Framework
{
    /**
     * @var array
     */
    private static $argv;
    /**
     * @var Server
     */
    public static $server;
    /**
     * @var array|bool|mixed|null
     */
    private $server_set;

    public function __construct($args = []) {
        $tty_width = $this->getTtyWidth();
        if (LOAD_MODE == 0) define("WORKING_DIR", getcwd());
        elseif (LOAD_MODE == 1) define("WORKING_DIR", realpath(__DIR__ . "/../../"));
        elseif (LOAD_MODE == 2) echo "Phar mode: " . WORKING_DIR . PHP_EOL;
        require_once "Utils/DataProvider.php";
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

        self::$argv = $args;

        $this->defineProperties();
        ZMBuf::initAtomic();
        ManagerGM::init(1024, 0.2, [
            [
                "key" => "connect_id",
                "type" => "string",
                "size" => 30
            ]
        ]);
        //start swoole Framework
        $this->selfCheck();
        try {
            self::$server = new Server(ZMConfig::get("global", "host"), ZMConfig::get("global", "port"));
            $this->server_set = ZMConfig::get("global", "swoole");
            Console::init(
                ZMConfig::get("global", "info_level"),
                self::$server,
                $args["log-theme"] ?? "default",
                ($o = ZMConfig::get("console_color")) === false ? [] : $o
            );
            // 注册 Swoole Server 的事件
            $this->registerServerEvents();

            $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
            date_default_timezone_set($timezone);

            $this->parseCliArgs(self::$argv);

            $out = [
                "host" => ZMConfig::get("global", "host"),
                "port" => ZMConfig::get("global", "port"),
                "log_level" => Console::getLevel(),
                "version" => ZM_VERSION,
                "config" => $args["env"] === null ? 'global.php' : $args["env"],
                "working_dir" => DataProvider::getWorkingDir()
            ];
            if (isset(ZMConfig::get("global", "swoole")["task_worker_num"])) {
                $out["task_worker_num"] = ZMConfig::get("global", "swoole")["task_worker_num"];
            }
            if (($num = ZMConfig::get("global", "swoole")["worker_num"] ?? swoole_cpu_num()) != 1) {
                $out["worker_num"] = $num;
            }
            $store = "";
            foreach ($out as $k => $v) {
                $line = $k . ": " . $v;
                if (strlen($line) > 19 && $store == "" || $tty_width < 53) {
                    Console::log($line);
                } else {
                    if ($store === "") $store = str_pad($line, 19, " ", STR_PAD_RIGHT);
                    else {
                        $store .= (" |   " . $line);
                        Console::log($store);
                        $store = "";
                    }
                }
            }
            if ($store != "") Console::log($store);

            self::$server->set($this->server_set);
            if (file_exists(DataProvider::getWorkingDir() . "/config/motd.txt")) {
                $motd = file_get_contents(DataProvider::getWorkingDir() . "/config/motd.txt");
            } else {
                $motd = file_get_contents(__DIR__."/../../config/motd.txt");
            }
            $motd = explode("\n", $motd);
            foreach ($motd as $k => $v) {
                $motd[$k] = substr($v, 0, $tty_width);
            }
            $motd = implode("\n", $motd);
            echo $motd;
            global $asd;
            $asd = get_included_files();
            self::$server->start();
        } catch (Exception $e) {
            Console::error("Framework初始化出现错误，请检查！");
            Console::error($e->getMessage());
            die;
        }
    }

    private function defineProperties() {
        define("ZM_START_TIME", microtime(true));
        define("ZM_DATA", ZMConfig::get("global", "zm_data"));
        define("ZM_VERSION", json_decode(file_get_contents(__DIR__ . "/../../composer.json"), true)["version"] ?? "unknown");
        define("CONFIG_DIR", ZMConfig::get("global", "config_dir"));
        define("CRASH_DIR", ZMConfig::get("global", "crash_dir"));
        @mkdir(ZM_DATA);
        @mkdir(CONFIG_DIR);
        @mkdir(CRASH_DIR);
        define("ZM_MATCH_ALL", 0);
        define("ZM_MATCH_FIRST", 1);
        define("ZM_MATCH_NUMBER", 2);
        define("ZM_MATCH_SECOND", 3);
        define("ZM_BREAKPOINT", 'if(Framework::$argv["debug-mode"]) extract(\Psy\debug(get_defined_vars(), isset($this) ? $this : @get_called_class()));');
        define("BP", ZM_BREAKPOINT);
        define("ZM_DEFAULT_FETCH_MODE", 4);
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

    /**
     * 从全局配置文件里读取注入系统事件的类
     * @throws ReflectionException
     * @throws ReflectionException
     */
    private function registerServerEvents() {
        $all_event_class = ZMConfig::get("global", "server_event_handler_class") ?? [];
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
            self::$server->on($k, function (...$param) use ($v) {
                $c = $v->class;
                //echo $c.PHP_EOL;
                $c = new $c();
                call_user_func_array([$c, $v->method], $param);
            });
        }
    }

    /**
     * 解析命令行的 $argv 参数们
     * @param $args
     * @throws Exception
     */
    private function parseCliArgs($args) {
        $coroutine_mode = true;
        global $terminal_id;
        $terminal_id = call_user_func(function () {
            try {
                $data = random_bytes(16);
            } catch (Exception $e) {
                return "";
            }
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
        });
        foreach ($args as $x => $y) {
            switch ($x) {
                case 'debug-mode':
                    if ($y) {
                        $coroutine_mode = false;
                        $terminal_id = null;
                        Console::warning("You are in debug mode, do not use in production!");
                    }
                    break;
                case 'daemon':
                    if ($y) {
                        $this->server_set["daemonize"] = 1;
                        Console::log("已启用守护进程，输出重定向到 " . $this->server_set["log_file"]);
                        $terminal_id = null;
                    }
                    break;
                case 'disable-console-input':
                    if ($y) $terminal_id = null;
                    break;
                case 'remote-shell':
                    if ($y) {
                        $host = "127.0.0.1";
                        $port = 9599;
                        RemoteShell::listen(self::$server, $host, $port);
                        Console::log(Console::setColor("正在监听" . $host . ":" . strval($port) . "的调试接口，请注意安全", "yellow"));
                    }
                    break;
                case 'log-error':
                    if ($y) Console::setLevel(0);
                    break;
                case 'log-warning':
                    if ($y) Console::setLevel(1);
                    break;
                case 'log-info':
                    if ($y) Console::setLevel(2);
                    break;
                case 'log-verbose':
                    if ($y) Console::setLevel(3);
                    break;
                case 'log-debug':
                    if ($y) Console::setLevel(4);
                    break;
                case 'log-theme':
                    if($y !== null) {
                        Console::$theme = $y;
                    }
                    break;
            }
        }
        if ($coroutine_mode) Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
    }

    private function getTtyWidth() {
        return explode(" ", trim(exec("stty size")))[1];
    }

    public static function getServer() {
        return self::$server;
    }
}
