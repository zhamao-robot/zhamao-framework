<?php


namespace ZM;


use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use ZM\Annotation\Swoole\OnSetup;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Event\ServerEventHandler;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\DataProvider;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Swoole\Runtime;
use Swoole\WebSocket\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Utils\ZMUtil;

class Framework
{
    /**
     * @var array
     */
    public static $argv;
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

        self::$argv = $args;

        //定义常量
        include_once "global_defines.php";

        ZMConfig::setDirectory(DataProvider::getWorkingDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die ("Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\n");
        }
        ZMAtomic::init();
        try {
            $sw = ZMConfig::get("global");
            if (!is_dir($sw["zm_data"])) mkdir($sw["zm_data"]);
            if (!is_dir($sw["config_dir"])) mkdir($sw["config_dir"]);
            if (!is_dir($sw["crash_dir"])) mkdir($sw["crash_dir"]);
            ManagerGM::init(ZMConfig::get("global", "swoole")["max_connection"] ?? 2048, 0.5, [
                [
                    "key" => "connect_id",
                    "type" => "string",
                    "size" => 30
                ],
                [
                    "key" => "type",
                    "type" => "int"
                ]
            ]);
        } catch (ConnectionManager\TableException $e) {
            die($e->getMessage());
        }
        try {
            self::$server = new Server(ZMConfig::get("global", "host"), ZMConfig::get("global", "port"));
            $this->server_set = ZMConfig::get("global", "swoole");
            Console::init(
                ZMConfig::get("global", "info_level") ?? 2,
                self::$server,
                $args["log-theme"] ?? "default",
                ($o = ZMConfig::get("console_color")) === false ? [] : $o
            );

            $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
            date_default_timezone_set($timezone);

            $this->parseCliArgs(self::$argv);

            $out = [
                "host" => ZMConfig::get("global", "host"),
                "port" => ZMConfig::get("global", "port"),
                "log_level" => Console::getLevel(),
                "version" => ZM_VERSION,
                "config" => $args["env"] === null ? 'global.php' : $args["env"]
            ];
            if (APP_VERSION !== "unknown") $out["app_version"] = APP_VERSION;
            if (isset(ZMConfig::get("global", "swoole")["task_worker_num"])) {
                $out["task_worker_num"] = ZMConfig::get("global", "swoole")["task_worker_num"];
            }
            if (($num = ZMConfig::get("global", "swoole")["worker_num"] ?? swoole_cpu_num()) != 1) {
                $out["worker_num"] = $num;
            }
            $out["working_dir"] = DataProvider::getWorkingDir();
            Console::printProps($out, $tty_width);

            self::$server->set($this->server_set);
            if (file_exists(DataProvider::getWorkingDir() . "/config/motd.txt")) {
                $motd = file_get_contents(DataProvider::getWorkingDir() . "/config/motd.txt");
            } else {
                $motd = file_get_contents(__DIR__ . "/../../config/motd.txt");
            }
            $motd = explode("\n", $motd);
            foreach ($motd as $k => $v) {
                $motd[$k] = substr($v, 0, $tty_width);
            }
            $motd = implode("\n", $motd);
            echo $motd;
            global $asd;
            $asd = get_included_files();
            // 注册 Swoole Server 的事件
            $this->registerServerEvents();
            $r = ZMConfig::get("global", "light_cache") ?? [
                    "size" => 1024,
                    "max_strlen" => 8192,
                    "hash_conflict_proportion" => 0.6,
                    "persistence_path" => realpath(DataProvider::getDataFolder() . "_cache.json"),
                    "auto_save_interval" => 900
                ];
            LightCache::init($r);
            LightCacheInside::init();
            SpinLock::init($r["size"]);
            set_error_handler(function ($error_no, $error_msg, $error_file, $error_line) {
                switch ($error_no) {
                    case E_WARNING:
                        $level_tips = 'PHP Warning: ';
                        break;
                    case E_NOTICE:
                        $level_tips = 'PHP Notice: ';
                        break;
                    case E_DEPRECATED:
                        $level_tips = 'PHP Deprecated: ';
                        break;
                    case E_USER_ERROR:
                        $level_tips = 'User Error: ';
                        break;
                    case E_USER_WARNING:
                        $level_tips = 'User Warning: ';
                        break;
                    case E_USER_NOTICE:
                        $level_tips = 'User Notice: ';
                        break;
                    case E_USER_DEPRECATED:
                        $level_tips = 'User Deprecated: ';
                        break;
                    case E_STRICT:
                        $level_tips = 'PHP Strict: ';
                        break;
                    default:
                        $level_tips = 'Unkonw Type Error: ';
                        break;
                }      // do some handle
                $error = $level_tips . $error_msg . ' in ' . $error_file . ' on ' . $error_line;
                Console::warning($error);      // 如果 return false 则错误会继续递交给 PHP 标准错误处理     /
                return true;
            }, E_ALL | E_STRICT);
        } catch (Exception $e) {
            Console::error("Framework初始化出现错误，请检查！");
            Console::error($e->getMessage());
            die;
        }
    }

    public function start() {
        self::$server->start();
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
                    if ($annotation instanceof SwooleHandler) {
                        $annotation->class = $v;
                        $annotation->method = $vs->getName();
                        $event_list[strtolower($annotation->event)] = $annotation;
                    } elseif ($annotation instanceof OnSetup) {
                        $annotation->class = $v;
                        $annotation->method = $vs->getName();
                        $c = new $v();
                        $m = $annotation->method;
                        $c->$m();
                    }
                }
            }
        }
        foreach ($event_list as $k => $v) {
            $c = ZMUtil::getModInstance($v->class);
            $m = $v->method;
            self::$server->on($k, function (...$param) use ($c, $m) { $c->$m(...$param); });
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
                case 'disable-coroutine':
                    if ($y) {
                        $coroutine_mode = false;
                    }
                    break;
                case 'debug-mode':
                    if ($y || ZMConfig::get("global", "debug_mode")) {
                        $coroutine_mode = false;
                        $terminal_id = null;
                        Console::warning("You are in debug mode, do not use in production!");
                    }
                    break;
                case 'daemon':
                    if ($y) {
                        $this->server_set["daemonize"] = 1;
                        Console::$theme = "no-color";
                        Console::log("已启用守护进程，输出重定向到 " . $this->server_set["log_file"]);
                        $terminal_id = null;
                    }
                    break;
                case 'disable-console-input':
                case 'no-interaction':
                    if ($y) $terminal_id = null;
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
                case 'verbose':
                    if ($y) Console::setLevel(3);
                    break;
                case 'log-debug':
                    if ($y) Console::setLevel(4);
                    break;
                case 'log-theme':
                    if ($y !== null) {
                        Console::$theme = $y;
                    }
                    break;
                default:
                    //Console::info("Calculating ".$x);
                    //dump($y);
                    break;
            }
        }
        if ($coroutine_mode) Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
    }

    public static function getTtyWidth() {
        return explode(" ", trim(exec("stty size")))[1];
    }
}
