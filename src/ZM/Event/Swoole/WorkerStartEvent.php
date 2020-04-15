<?php


namespace ZM\Event\Swoole;


use Co;
use Doctrine\Common\Annotations\AnnotationException;
use Exception;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Timer;
use ZM\Annotation\AnnotationBase;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Swoole\OnStart;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\Connection\ConnectionManager;
use ZM\Context\ContextInterface;
use ZM\DB\DB;
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
        $this->resetConnections();//释放所有与framework的连接

        //设置炸毛buf中储存的对象
        ZMBuf::$globals = new GlobalConfig();
        ZMBuf::$config = [];
        $file = scandir(WORKING_DIR . '/config/');
        unset($file[0], $file[1]);
        foreach ($file as $k => $v) {
            if ($v == "global.php") continue;
            $name = explode(".", $v);
            if (($prefix = end($name)) == "json") {
                ZMBuf::$config[$name[0]] = json_decode(Co::readFile(WORKING_DIR . '/config/' . $v), true);
                Console::info("已读取配置文件(json)：" . $prefix);
            } elseif ($prefix == "php") {
                ZMBuf::$config[$name[0]] = include_once WORKING_DIR . '/config/' . $v;
                if (is_array(ZMBuf::$config[$name[0]]))
                    Console::info("已读取配置文件(php)：" . $prefix);
            }
        }
        if (ZMBuf::globals("sql_config")["sql_host"] != "") {
            Console::info("新建SQL连接池中");
            ZMBuf::$sql_pool = new SQLPool();
            DB::initTableList();
        }
        ZMBuf::$server = $this->server;
        ZMBuf::$atomics['reload_time']->add(1);

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
        foreach(ZMBuf::$events[OnStart::class] ?? [] as $v) {
            $class_name = $v->class;
            /** @var ModBase $class */
            $class = new $class_name(["server" => $this->server, "worker_id" => $this->worker_id], ModHandleType::SWOOLE_WORKER_START);
            call_user_func_array([$class, $v->method], []);
        }
        set_coroutine_params(["server" => $this->server, "worker_id" => $this->worker_id]);
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
     * @throws Exception
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
        require_once WORKING_DIR . "/vendor/autoload.php";

        //加载各个模块的注解类，以及反射
        Console::info("检索Module中");
        AnnotationParser::registerMods();

        //加载Custom目录下的自定义的内部类
        ConnectionManager::registerCustomClass();

        //加载自定义的全局函数
        if(file_exists(WORKING_DIR."/src/Custom/global_function.php"))
            require_once WORKING_DIR."/src/Custom/global_function.php";
        $this->afterCheck();
    }

    private function setAutosaveTimer($globals) {
        DataProvider::$buffer_list = [];
        Timer::tick($globals * 1000, function () {
            DataProvider::saveBuffer();
        });
    }

    /**
     * @throws Exception
     */
    private function afterCheck() {
        $context_class = ZMBuf::globals("context_class");
        if(!is_a($context_class, ContextInterface::class, true)) {
            throw new Exception("Context class must implemented from ContextInterface!");
        }
    }
}
