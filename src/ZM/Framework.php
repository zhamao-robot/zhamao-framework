<?php


namespace ZM;


use Doctrine\Common\Annotations\AnnotationReader;
use Error;
use Exception;
use Swoole\Server\Port;
use ZM\Annotation\Swoole\OnSetup;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\TermColor;
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
use ZM\Utils\Terminal;
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
     * @var string[]
     */
    public static $loaded_files = [];
    /**
     * @var array|bool|mixed|null
     */
    private $server_set;

    /** @noinspection PhpUnusedParameterInspection */
    public function __construct($args = []) {
        $tty_width = $this->getTtyWidth();

        self::$argv = $args;

        ZMConfig::setDirectory(DataProvider::getWorkingDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die ("Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\n");
        }

        //定义常量
        include_once "global_defines.php";

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
            Console::init(
                ZMConfig::get("global", "info_level") ?? 2,
                self::$server,
                $args["log-theme"] ?? "default",
                ($o = ZMConfig::get("console_color")) === false ? [] : $o
            );

            $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
            date_default_timezone_set($timezone);

            $this->server_set = ZMConfig::get("global", "swoole");
            $this->server_set["log_level"] = SWOOLE_LOG_DEBUG;
            $add_port = ZMConfig::get("global", "remote_terminal")["status"] ?? false;

            $this->parseCliArgs(self::$argv, $add_port);
            $worker = $this->server_set["worker_num"] ?? swoole_cpu_num();
            define("ZM_WORKER_NUM", $worker);
            ZMAtomic::init();
            // 打印初始信息
            $out["listen"] = ZMConfig::get("global", "host") . ":" . ZMConfig::get("global", "port");
            if (!isset($this->server_set["worker_num"])) $out["worker"] = swoole_cpu_num() . " (auto)";
            else $out["worker"] = $this->server_set["worker_num"];
            $out["environment"] = $args["env"] === null ? "default" : $args["env"];
            $out["log_level"] = Console::getLevel();
            $out["version"] = ZM_VERSION . (LOAD_MODE == 0 ? (" (build " . ZM_VERSION_ID . ")") : "");
            if (APP_VERSION !== "unknown") $out["app_version"] = APP_VERSION;
            if (isset($this->server_set["task_worker_num"])) {
                $out["task_worker"] = $this->server_set["task_worker_num"];
            }
            if (ZMConfig::get("global", "sql_config")["sql_host"] !== "") {
                $conf = ZMConfig::get("global", "sql_config");
                $out["mysql_pool"] = $conf["sql_database"] . "@" . $conf["sql_host"] . ":" . $conf["sql_port"];
            }
            if (ZMConfig::get("global", "redis_config")["host"] !== "") {
                $conf = ZMConfig::get("global", "redis_config");
                $out["redis_pool"] = $conf["host"] . ":" . $conf["port"];
            }
            if (ZMConfig::get("global", "static_file_server")["status"] !== false) {
                $out["static_file_server"] = "enabled";
            }
            if (self::$argv["show-php-ver"] !== false) {
                $out["php_version"] = PHP_VERSION;
                $out["swoole_version"] = SWOOLE_VERSION;
            }
            if ($add_port) {
                $conf = ZMConfig::get("global", "remote_terminal");
                $out["terminal"] = $conf["host"] . ":" . $conf["port"];
            }

            $out["working_dir"] = DataProvider::getWorkingDir();
            self::printProps($out, $tty_width, $args["log-theme"] === null);

            self::$server = new Server(ZMConfig::get("global", "host"), ZMConfig::get("global", "port"));

            if ($add_port) {
                $conf = ZMConfig::get("global", "remote_terminal") ?? [
                        'status' => true,
                        'host' => '127.0.0.1',
                        'port' => 20002,
                        'token' => ''
                    ];
                $welcome_msg = Console::setColor("Welcome! You can use `help` for usage.", "green");
                /** @var Port $port */
                $port = self::$server->listen($conf["host"], $conf["port"], SWOOLE_SOCK_TCP);
                $port->set([
                    'open_http_protocol' => false
                ]);
                $port->on('connect', function (?\Swoole\Server $serv, $fd) use ($port, $welcome_msg, $conf) {
                    ManagerGM::pushConnect($fd, "terminal");
                    $serv->send($fd, file_get_contents(working_dir() . "/config/motd.txt"));
                    if (!empty($conf["token"])) {
                        $serv->send($fd, "Please input token: ");
                    } else {
                        $serv->send($fd, $welcome_msg . "\n>>> ");
                    }
                });

                $port->on('receive', function ($serv, $fd, $reactor_id, $data) use ($welcome_msg, $conf) {
                    ob_start();
                    try {
                        $arr = LightCacheInside::get("light_array", "input_token") ?? [];
                        if (empty($arr[$fd] ?? '')) {
                            if ($conf["token"] != '') {
                                $token = trim($data);
                                if ($token === $conf["token"]) {
                                    SpinLock::transaction("input_token", function () use ($fd, $token) {
                                        $arr = LightCacheInside::get("light_array", "input_token");
                                        $arr[$fd] = $token;
                                        LightCacheInside::set("light_array", "input_token", $arr);
                                    });
                                    $serv->send($fd, Console::setColor("Auth success!!\n", "green"));
                                    $serv->send($fd, $welcome_msg . "\n>>> ");
                                } else {
                                    $serv->send($fd, Console::setColor("Auth failed!!\n", "red"));
                                    $serv->close($fd);
                                }
                                return;
                            }
                        }
                        if (trim($data) == "exit" || trim($data) == "q") {
                            $serv->send($fd, Console::setColor("Bye!\n", "blue"));
                            $serv->close($fd);
                            return;
                        }
                        Terminal::executeCommand(trim($data));
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                        Console::error("Uncaught exception " . get_class($e) . " when calling \"open\": " . $error_msg);
                        Console::trace();
                    } catch (Error $e) {
                        $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                        Console::error("Uncaught " . get_class($e) . " when calling \"open\": " . $error_msg);
                        Console::trace();
                    }

                    $r = ob_get_clean();
                    if (!empty($r)) $serv->send($fd, $r);
                    if (!in_array(trim($data), ['r', 'reload', 'stop'])) $serv->send($fd, ">>> ");
                });

                $port->on('close', function ($serv, $fd) {
                    ManagerGM::popConnect($fd);
                    //echo "Client: Close.\n";
                });
            }

            self::$server->set($this->server_set);
            Console::setServer(self::$server);
            self::printMotd($tty_width);

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
            Console::debug($e);
            die;
        }
    }

    private static function printMotd($tty_width) {
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
    }

    public function start() {
        self::$loaded_files = get_included_files();
        self::$server->start();
        zm_atomic("server_is_stopped")->set(1);
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
     * @param $add_port
     */
    private function parseCliArgs($args, &$add_port) {
        $coroutine_mode = true;
        global $terminal_id;
        $terminal_id = uuidgen();
        foreach ($args as $x => $y) {
            switch ($x) {
                case 'worker-num':
                    if (intval($y) >= 1 && intval($y) <= 1024) {
                        $this->server_set["worker_num"] = intval($y);
                    } else {
                        Console::warning("Invalid worker num! Turn to default value (".($this->server_set["worker_num"] ?? swoole_cpu_num()).")");
                    }
                    break;
                case 'task-worker-num':
                    if (intval($y) >= 1 && intval($y) <= 1024) {
                        $this->server_set["task_worker_num"] = intval($y);
                        $this->server_set["task_enable_coroutine"] = true;
                    } else {
                        Console::warning("Invalid worker num! Turn to default value (0)");
                    }
                    break;
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
                case 'remote-terminal':
                    $add_port = true;
                    break;
                case 'show-php-ver':
                default:
                    //Console::info("Calculating ".$x);
                    //dump($y);
                    break;
            }
        }
        if ($coroutine_mode) Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL);
        else Runtime::enableCoroutine(false, SWOOLE_HOOK_ALL);
    }

    private static function writeNoDouble($k, $v, &$line_data, &$line_width, &$current_line, $colorful, $max_border) {
        $tmp_line = $k . ": " . $v;
        //Console::info("写入[".$tmp_line."]");
        if (strlen($tmp_line) >= $line_width[$current_line]) { //输出的内容太多了，以至于一行都放不下一个，要折行
            $title_strlen = strlen($k . ": ");
            $content_len = $line_width[$current_line] - $title_strlen;

            $line_data[$current_line] = " " . $k . ": ";
            if ($colorful) $line_data[$current_line] .= TermColor::color8(32);
            $line_data[$current_line] .= substr($v, 0, $content_len);
            if ($colorful) $line_data[$current_line] .= TermColor::RESET;
            $rest = substr($v, $content_len);
            ++$current_line; // 带标题的第一行满了，折到第二行
            do {
                if ($colorful) $line_data[$current_line] = TermColor::color8(32);
                $line_data[$current_line] .= " " . substr($rest, 0, $max_border - 2);
                if ($colorful) $line_data[$current_line] .= TermColor::RESET;
                $rest = substr($rest, $max_border - 2);
                ++$current_line;
            } while ($rest > $max_border - 2); // 循环，直到放完
        } else { // 不需要折行
            //Console::info("不需要折行");
            $line_data[$current_line] = " " . $k . ": ";
            if ($colorful) $line_data[$current_line] .= TermColor::color8(32);
            $line_data[$current_line] .= $v;
            if ($colorful) $line_data[$current_line] .= TermColor::RESET;

            if ($max_border >= 57) {
                if (strlen($tmp_line) >= intval(($max_border - 2) / 2)) {  // 不需要折行，直接输出一个转下一行
                    //Console::info("不需要折行，直接输出一个转下一行");
                    ++$current_line;
                } else {  // 输出很小，写到前面并分片
                    //Console::info("输出很小，写到前面并分片");
                    $space = intval($max_border / 2) - 2 - strlen($tmp_line);
                    $line_data[$current_line] .= str_pad("", $space);
                    $line_data[$current_line] .= "|  "; // 添加分片
                    $line_width[$current_line] -= (strlen($tmp_line) + 3 + $space);
                }
            } else {
                ++$current_line;
            }
        }
    }

    public static function printProps($out, $tty_width, $colorful = true) {
        $max_border = $tty_width < 65 ? $tty_width : 65;
        if (LOAD_MODE == 0) echo Console::setColor("* Framework started with source mode.\n", $colorful ? "yellow" : "");
        echo str_pad("", $max_border, "=") . PHP_EOL;

        $current_line = 0;
        $line_width = [];
        $line_data = [];
        foreach ($out as $k => $v) {
            start:
            if (!isset($line_width[$current_line])) {
                $line_width[$current_line] = $max_border - 2;
            }
            //Console::info("行宽[$current_line]：".$line_width[$current_line]);
            if ($max_border >= 57) { // 很宽的时候，一行能放两个短行
                if ($line_width[$current_line] == ($max_border - 2)) { //空行
                    self::writeNoDouble($k, $v, $line_data, $line_width, $current_line, $colorful, $max_border);
                } else { // 不是空行，已经有东西了
                    $tmp_line = $k . ": " . $v;
                    //Console::info("[$current_line]即将插入后面的东西[".$tmp_line."]");
                    if (strlen($tmp_line) > $line_width[$current_line]) { // 地方不够，另起一行
                        $line_data[$current_line] = str_replace("|  ", "", $line_data[$current_line]);
                        ++$current_line;
                        goto start;
                    } else { // 地方够，直接写到后面并另起一行
                        $line_data[$current_line] .= $k . ": ";
                        if ($colorful) $line_data[$current_line] .= TermColor::color8(32);
                        $line_data[$current_line] .= $v;
                        if ($colorful) $line_data[$current_line] .= TermColor::RESET;
                        ++$current_line;
                    }
                }
            } else {  // 不够宽，直接写单行
                self::writeNoDouble($k, $v, $line_data, $line_width, $current_line, $colorful, $max_border);
            }
        }
        foreach ($line_data as $v) {
            echo $v . PHP_EOL;
        }
        echo str_pad("", $max_border, "=") . PHP_EOL;
    }

    public static function getTtyWidth(): string {
        return explode(" ", trim(exec("stty size")))[1];
    }
}
