<?php


namespace ZM\Event\Swoole;


use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Timer;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\MappingNode;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Connection\ConnectionManager;
use ZM\DB\DB;
use ZM\DBCache\DBCacheManager;
use Framework\Console;
use Framework\GlobalConfig;
use Framework\ZMBuf;
use Swoole\Server;
use ZM\ModBase;
use ZM\ModHandleType;
use ZM\Utils\DataProvider;
use ZM\Utils\SQLPool;

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
     */
    public function onActivate(): WorkerStartEvent {
        Console::info("Worker启动中");
        ZMBuf::resetCache(); //清空变量缓存
        ZMBuf::set("wait_start", []); //添加队列，在workerStart运行完成前先让其他协程等待执行
        DBCacheManager::freeAllCache(); // 清空数据库缓存
        $this->resetConnections();//释放所有与framework的连接

        //设置炸毛buf中储存的对象
        ZMBuf::$globals = new GlobalConfig();
        if (ZMBuf::globals("sql_config")["sql_host"] != "") {
            Console::info("新建SQL连接池中");
            ZMBuf::$sql_pool = new SQLPool();
            DB::initTableList();
        }
        ZMBuf::$server = $this->server;
        ZMBuf::$atomics['reload_time']->add(1);
        ZMBuf::$req_mapping_node = new MappingNode("/");

        Console::info("监听console输入");
        Console::listenConsole(); //这个方法只能在这里调用，且如果worker_num不为1的话，此功能不可用

        $this->loadAllClass(); //加载composer资源、phar外置包、注解解析注册等

        $this->setAutosaveTimer(ZMBuf::globals("auto_save_interval"));

        return $this;
    }

    public function onAfter(): WorkerStartEvent {
        foreach (ZMBuf::get("wait_start") as $v) {
            Coroutine::resume($v);
        }
        ZMBuf::unsetCache("wait_start");
        foreach (ZMBuf::$events[SwooleEventAfter::class] ?? [] as $v) {
            /** @var AnnotationBase $v */
            if (strtolower($v->type) == "workerstart") {
                $class_name = $v->class;
                /** @var ModBase $class */
                $class = new $class_name(["server" => $this->server, "worker_id" => $this->worker_id], ModHandleType::SWOOLE_WORKER_START);
                call_user_func_array([$class, $v->method], []);
                if ($class->block_continue) break;
            }
        }
        return $this;
    }

    private function resetConnections() {
        foreach ($this->server->connections as $v) {
            $this->server->close($v);
        }
        if (ZMBuf::$sql_pool instanceof SqlPool) {
            ZMBuf::$sql_pool->destruct();
            ZMBuf::$sql_pool = null;
        }
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function loadAllClass() {
        //加载phar包
        Console::info("加载外部phar包中");
        $dir = WORKING_DIR . "/resources/package/";
        if (is_dir($dir)) {
            $list = scandir($dir);
            unset($list[0], $list[1]);
            foreach ($list as $v) {
                if (is_dir($dir . $v)) continue;
                if (pathinfo($dir . $v, 4) == "phar") require_once($dir . $v);
            }
        }

        //加载composer类
        Console::info("加载composer资源中");
        /** @noinspection PhpIncludeInspection */
        require_once WORKING_DIR . "/vendor/autoload.php";

        //加载各个模块的注解类，以及反射
        Console::info("检索Module中");
        AnnotationParser::registerMods();

        //加载Custom目录下的自定义的内部类
        ConnectionManager::registerCustomClass();
    }

    private function setAutosaveTimer($globals) {
        DataProvider::$buffer_list = [];
        Timer::tick($globals * 1000, function() {
            DataProvider::saveBuffer();
        });
    }
}