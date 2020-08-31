<?php


namespace ZM\Event\Swoole;


use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use PDO;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Process;
use Swoole\Timer;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Swoole\OnStart;
use ZM\Config\ZMConfig;
use ZM\Context\ContextInterface;
use ZM\DB\DB;
use ZM\Console\Console;
use Swoole\Server;
use ZM\Event\EventHandler;
use ZM\Exception\DbException;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

class WorkerStartEvent implements SwooleEvent
{
    private $worker_id;
    /**
     * @var Server
     */
    private $server;

    public function __construct(Server $server, $worker_id) {
        $this->server = $server;
        $this->worker_id = $worker_id;
    }

    /**
     * @return WorkerStartEvent
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws DbException
     */
    public function onActivate(): WorkerStartEvent {

        Console::info("Worker #{$this->server->worker_id} 启动中");
        ZMBuf::$server = $this->server;
        ZMBuf::resetCache(); //清空变量缓存
        ZMBuf::set("wait_start", []); //添加队列，在workerStart运行完成前先让其他协程等待执行
        $this->resetConnections();//释放所有与framework的连接

        global $terminal_id;

        Terminal::listenConsole($terminal_id); //这个方法只能在这里调用，且如果worker_num不为1的话，此功能不可用
        // 这里执行的是只需要执行一遍的代码，比如终端监听器和键盘监听器
        if ($this->server->worker_id === 0) {
            if($terminal_id !== null) Console::info("监听console输入");
            Process::signal(SIGINT, function () {
                echo PHP_EOL;
                Console::warning("Server interrupted by keyboard.");
                ZMUtil::stop();
            });
            ZMBuf::$atomics['reload_time']->add(1);
            $this->setAutosaveTimer(ZMConfig::get("global", "auto_save_interval"));
        } else {
            Process::signal(SIGINT, function () {
                // Do Nothing
            });
        }
        if (ZMConfig::get("global", "sql_config")["sql_host"] != "") {
            Console::info("新建SQL连接池中");
            ob_start();
            phpinfo();
            $str = ob_get_clean();
            $str = explode("\n", $str);
            foreach ($str as $k => $v) {
                $v = trim($v);
                if ($v == "") continue;
                if (mb_strpos($v, "API Extensions") === false) continue;
                if (mb_strpos($v, "pdo_mysql") === false) {
                    throw new DbException("未安装 mysqlnd php-mysql扩展。");
                }
            }
            $sql = ZMConfig::get("global", "sql_config");
            ZMBuf::$sql_pool = new PDOPool((new PDOConfig())
                ->withHost($sql["sql_host"])
                ->withPort($sql["sql_port"])
                // ->withUnixSocket('/tmp/mysql.sock')
                ->withDbName($sql["sql_database"])
                ->withCharset('utf8mb4')
                ->withUsername($sql["sql_username"])
                ->withPassword($sql["sql_password"])
                ->withOptions($sql["sql_options"] ?? [PDO::ATTR_STRINGIFY_FETCHES => false])
            );
            DB::initTableList();
        }

        $this->loadAllClass(); //加载composer资源、phar外置包、注解解析注册等
        return $this;
    }

    /**
     * @return WorkerStartEvent
     * @throws AnnotationException
     */
    public function onAfter(): WorkerStartEvent {
        foreach (ZMBuf::get("wait_start") as $v) {
            Coroutine::resume($v);
        }
        ZMBuf::unsetCache("wait_start");
        set_coroutine_params(["server" => $this->server, "worker_id" => $this->worker_id]);
        if($this->server->worker_id === 0) {
            foreach (ZMBuf::$events[OnStart::class] ?? [] as $v) {
                $class_name = $v->class;
                Console::debug("正在调用启动时函数: " . $class_name . " -> " . $v->method);
                EventHandler::callWithMiddleware($class_name, $v->method, ["server" => $this->server, "worker_id" => $this->worker_id], []);
            }
            Console::debug("@OnStart 执行完毕");
        }
        return $this;
    }

    private function resetConnections() {
        foreach ($this->server->connections as $v) {
            $this->server->close($v);
        }
        if (ZMBuf::$sql_pool !== null) {
            ZMBuf::$sql_pool->close();
            ZMBuf::$sql_pool = null;
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function loadAllClass() {
        //加载phar包
        Console::info("加载外部phar包中");
        $dir = DataProvider::getWorkingDir() . "/resources/package/";
        if (version_compare(SWOOLE_VERSION, "4.4.0", ">=")) Timer::clearAll();
        if (is_dir($dir)) {
            $list = scandir($dir);
            unset($list[0], $list[1]);
            foreach ($list as $v) {
                if (is_dir($dir . $v)) continue;
                if (pathinfo($dir . $v, 4) == "phar") {
                    Console::verbose("加载Phar: " . $dir . $v . " 中");
                    require_once($dir . $v);
                }
            }
        }
        //加载composer类
        //remove stupid duplicate code

        //加载各个模块的注解类，以及反射
        Console::info("检索Module中");
        $parser = new AnnotationParser();
        $parser->addRegisterPath(DataProvider::getWorkingDir() . "/src/Module/", "Module");
        $parser->registerMods();
        $parser->sortLevels();

        //加载自定义的全局函数
        Console::debug("加载自定义的全局函数中");
        $this->afterCheck();
    }

    private function setAutosaveTimer($globals) {
        DataProvider::$buffer_list = [];
        zm_timer_tick($globals * 1000, function () {
            DataProvider::saveBuffer();
        });
    }

    /**
     * @throws Exception
     */
    private function afterCheck() {
        $context_class = ZMConfig::get("global", "context_class");
        if (!is_a($context_class, ContextInterface::class, true)) {
            throw new Exception("Context class must implemented from ContextInterface!");
        }
    }
}
