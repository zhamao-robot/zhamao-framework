<?php


namespace Scheduler;


use Exception;
use Framework\Console;
use Framework\GlobalConfig;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Process;
use Swoole\WebSocket\Frame;

class Scheduler
{
    const PROCESS = 1;
    const REMOTE = 2;
    /**
     * @var Process
     */
    private $process = null;
    /**
     * @var int
     */
    private $m_pid;
    private $pid;
    /**
     * @var Scheduler
     */
    private static $instance;
    /**
     * @var GlobalConfig
     */
    private $settings;
    /**
     * @var Client
     */
    private $client;

    public function __construct($method = self::PROCESS, $option = []) {
        if (self::$instance !== null) die("Cannot run two scheduler in on process!");
        self::$instance = $this;
        if ($method == self::PROCESS) $this->initProcess();
        elseif ($method == self::REMOTE) $this->initRemote();
    }

    private function initProcess() { //TODO: 完成Process模式的代码
        $m_pid = posix_getpid();
        $this->process = new Process(function (Process $worker) use ($m_pid) { self::onWork($worker, $m_pid); }, false, 2, true);
        $this->pid = $this->process->start();
        while (1) {
            $ret = Process::wait();
            if ($ret) {
                $this->process = new Process(function (Process $worker) use ($m_pid) { self::onWork($worker, $m_pid); }, false, 2, true);
                $this->pid = $this->process->start();
                echo "Reboot done.\n";
            }
        }
    }

    private function initRemote() {
        define('WORKING_DIR', __DIR__ . '../..');
        $this->requireGlobalFunctions();
        $this->registerAutoloader('classLoader');
        $this->settings = new GlobalConfig();
        if (!$this->settings->success) die("Failed to load global config. Please check config/global.php file");
        $this->defineProperties();

        //start swoole Framework
        $this->selfCheck();
        try {
            $host = $this->settings->get("scheduler")["host"];
            $port = $this->settings->get("scheduler")["port"];
            $token = $this->settings->get("scheduler")["token"];
            $this->client = new Client($host, $port);
            $path = "/" . ($token != "" ? ("?token=" . urlencode($token)) : "");
            while (true) {
                if ($this->client->upgrade($path)) {
                    while (true) {
                        $recv = $this->client->recv();
                        if ($recv instanceof Frame) {
                            (new MessageEvent($this->client, $recv))->onActivate();
                        } else {
                            break;
                        }
                    }
                } else {
                    Console::warning("无法连接Framework，将在5秒后重连...");
                    Coroutine::sleep(5);
                }
            }
        } catch (Exception $e) {
            Console::error($e);
        }
    }

    private function requireGlobalFunctions() {
        /** @noinspection PhpIncludeInspection */
        require WORKING_DIR . '/src/Framework/global_functions.php';
    }

    private function registerAutoloader(string $string) {
        if (!spl_autoload_register($string)) die("Failed to register autoloader named \"$string\" !");
    }

    private function defineProperties() {
        define("ZM_START_TIME", microtime(true));
        define("ZM_DATA", $this->settings->get("zm_data"));
        //define("CONFIG_DIR", $this->settings->get("config_dir"));
        define("CRASH_DIR", $this->settings->get("crash_dir"));
    }

    private function selfCheck() {
        if (!extension_loaded("swoole")) die("Can not find swoole extension.\n");
        if (!extension_loaded("sockets")) die("Can not find sockets extension.\n");
        if (!function_exists("mb_substr")) die("Can not find mbstring extension.\n");
        if (substr(PHP_VERSION, 0, 1) != "7") die("PHP >=7 required.\n");
        //if (!class_exists("ZipArchive")) die("Can not find Zip extension.\n");
        if (!file_exists(CRASH_DIR . "last_error.log")) die("Can not find log file.\n");
        return true;
    }

    private static function onWork(Process $worker, $m_pid) {
        swoole_set_process_name('php-scheduler');
        for ($j = 0; $j < 16000; $j++) {
            self::checkMpid($worker, $m_pid);
            echo "msg: {$j}\n";
            sleep(1);
        }
    }

    private static function checkMpid(Process $worker, $m_pid) {
        if (!Process::kill($m_pid, 0)) {
            $worker->exit(); //主进程死了我也死
            // 这句提示,实际是看不到的.需要写到日志中
            echo "Master process exited, I [{$worker['pid']}] also quit\n";
        }
    }
}