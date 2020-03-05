<?php


namespace Framework;

use ZM\Event\EventHandler;
use Exception;
use Swoole\WebSocket\Server;
use ZM\Http\Response;

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

    public function __construct($args = []) {
        if (self::$instance !== null) die("Cannot run two FrameworkLoader in one process!");
        self::$instance = $this;
        self::$argv = $args;

        chdir(__DIR__ . '/../..');
        define('WORKING_DIR', getcwd());
        $this->requireGlobalFunctions();
        $this->registerAutoloader('classLoader');
        self::$settings = new GlobalConfig();
        ZMBuf::$globals = self::$settings;
        if (!self::$settings->success) die("Failed to load global config. Please check config/global.php file");
        $this->defineProperties();

        //start swoole Framework
        $this->selfCheck();
        try {
            $this->server = new Server(self::$settings->get("host"), self::$settings->get("port"));
            if (in_array("--remote-shell", $args)) RemoteShell::listen($this->server, "127.0.0.1");
            $this->server->set(self::$settings->get("swoole"));
            $this->server->on("WorkerStart", [$this, "onWorkerStart"]);
            $this->server->on("message", function ($server, $frame) { EventHandler::callSwooleEvent("message", $server, $frame); });
            $this->server->on("request", function ($request, $response) {
                $response = new Response($response);
                EventHandler::callSwooleEvent("request", $request, $response);
            });
            $this->server->on("open", function ($server, $request) { EventHandler::callSwooleEvent("open", $server, $request); });
            $this->server->on("close", function ($server, $fd) { EventHandler::callSwooleEvent("close", $server, $fd); });
            ZMBuf::initAtomic();
            Console::info("host: ".self::$settings->get("host").", port: ".self::$settings->get("port"));
            $this->server->start();
        } catch (Exception $e) {
            Console::error("Framework初始化出现错误，请检查！");
            Console::error($e->getMessage());
            die;
        }
    }

    private function requireGlobalFunctions() {
        require __DIR__ . '/global_functions.php';
    }

    private function registerAutoloader(string $string) {
        if (!spl_autoload_register($string)) die("Failed to register autoloader named \"$string\" !");
    }

    private function defineProperties() {
        define("ZM_START_TIME", microtime(true));
        define("ZM_DATA", self::$settings->get("zm_data"));
        define("CONFIG_DIR", self::$settings->get("config_dir"));
        define("CRASH_DIR", self::$settings->get("crash_dir"));
        @mkdir(ZM_DATA);
        @mkdir(CONFIG_DIR);
        @mkdir(CRASH_DIR);
        define("ZM_MATCH_ALL", 0);
        define("ZM_MATCH_FIRST", 1);
        define("ZM_MATCH_NUMBER", 2);
        define("ZM_MATCH_SECOND", 3);
    }

    private function selfCheck() {
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\n");
        //if (!extension_loaded("gd")) die("Can not find gd extension.\n");
        if (!extension_loaded("sockets")) die("Can not find sockets extension.\n");
        if (!function_exists("mb_substr")) die("Can not find mbstring extension.\n");
        if (substr(PHP_VERSION, 0, 1) != "7") die("PHP >=7 required.\n");
        //if (!function_exists("curl_exec")) die("Can not find curl extension.\n");
        //if (!class_exists("ZipArchive")) die("Can not find Zip extension.\n");
        //if (!file_exists(CRASH_DIR . "last_error.log")) die("Can not find log file.\n");
        return true;
    }

    public function onWorkerStart(\Swoole\Server $server, $worker_id) {
        self::$instance = $this;
        self::$run_time = microtime(true);
        EventHandler::callSwooleEvent("WorkerStart", $server, $worker_id);
    }
}