<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event\SwooleEvent;

use Error;
use Exception;
use PDO;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Process;
use Swoole\Server;
use Swoole\Timer;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Swoole\OnStart;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\DB\DB;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Event\SwooleEvent;
use ZM\Exception\DbException;
use ZM\Framework;
use ZM\Module\QQBot;
use ZM\Store\LightCacheInside;
use ZM\Store\MySQL\SqlPoolStorage;
use ZM\Store\Redis\ZMRedisPool;
use ZM\Utils\DataProvider;
use ZM\Utils\ProcessManager;

/**
 * Class OnWorkerStart
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("WorkerStart")
 */
class OnWorkerStart implements SwooleEvent
{
    public function onCall(Server $server, $worker_id) {
        if (!Framework::$argv["disable-safe-exit"]) {
            Process::signal(SIGINT, function () use ($worker_id, $server) {

            });
        }
        unset(Context::$context[Coroutine::getCid()]);
        if ($server->taskworker === false) {
            if (!Framework::$argv["disable-safe-exit"]) {
                Process::signal(SIGUSR1, function () use ($worker_id) {
                    Timer::clearAll();
                    ProcessManager::resumeAllWorkerCoroutines();
                });
            }
            zm_atomic("_#worker_" . $worker_id)->set($server->worker_pid);
            if (LightCacheInside::get("wait_api", "wait_api") !== null) {
                LightCacheInside::unset("wait_api", "wait_api");
            }
            try {
                register_shutdown_function(function () use ($server) {
                    $error = error_get_last();
                    if (($error["type"] ?? -1) != 0) {
                        Console::error("Internal fatal error: " . $error["message"] . " at " . $error["file"] . "({$error["line"]})");
                    }
                    //DataProvider::saveBuffer();
                    /** @var Server $server */
                    if (server() === null) $server->shutdown();
                    else server()->shutdown();
                });

                Console::verbose("Worker #{$server->worker_id} 启动中");
                Framework::$server = $server;
                //ZMBuf::resetCache(); //清空变量缓存
                //ZMBuf::set("wait_start", []); //添加队列，在workerStart运行完成前先让其他协程等待执行
                foreach ($server->connections as $v) {
                    $server->close($v);
                }

                //TODO: 单独抽出来MySQL和Redis连接池
                if (ZMConfig::get("global", "sql_config")["sql_host"] != "") {
                    if (SqlPoolStorage::$sql_pool !== null) {
                        SqlPoolStorage::$sql_pool->close();
                        SqlPoolStorage::$sql_pool = null;
                    }
                    Console::info("新建SQL连接池中");
                    ob_start();
                    phpinfo(); //这个phpinfo是有用的，不能删除
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
                    SqlPoolStorage::$sql_pool = new PDOPool((new PDOConfig())
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

                // 开箱即用的Redis
                $redis = ZMConfig::get("global", "redis_config");
                if ($redis !== null && $redis["host"] != "") {
                    if (!extension_loaded("redis")) Console::error("Can not find redis extension.\n");
                    else ZMRedisPool::init($redis);
                }

                $this->loadAnnotations(); //加载composer资源、phar外置包、注解解析注册等

                //echo json_encode(debug_backtrace(), 128|256);

                EventManager::registerTimerTick(); //启动计时器
                //ZMBuf::unsetCache("wait_start");
                set_coroutine_params(["server" => $server, "worker_id" => $worker_id]);
                $dispatcher = new EventDispatcher(OnStart::class);
                $dispatcher->setRuleFunction(function ($v) {
                    return server()->worker_id === $v->worker_id || $v->worker_id === -1;
                });
                $dispatcher->dispatchEvents($server, $worker_id);
                if ($dispatcher->status === EventDispatcher::STATUS_NORMAL) Console::debug("@OnStart 执行完毕");
                else Console::warning("@OnStart 执行异常！");
                Console::success("Worker #" . $worker_id . " started");
            } catch (Exception $e) {
                Console::error("Worker加载出错！停止服务！");
                Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                Process::kill($server->master_pid, SIGTERM);
                return;
            } catch (Error $e) {
                Console::error("PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                Console::error("Maybe it caused by your own code if in your own Module directory.");
                Console::log($e->getTraceAsString(), 'gray');
                Process::kill($server->master_pid, SIGTERM);
            }
        } else {
            // 这里是TaskWorker初始化的内容部分
            try {
                Framework::$server = $server;
                $this->loadAnnotations();
                Console::success("TaskWorker #" . $server->worker_id . " started");
            } catch (Exception $e) {
                Console::error("Worker加载出错！停止服务！");
                Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                Process::kill($server->master_pid, SIGTERM);
                return;
            } catch (Error $e) {
                Console::error("PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                Console::error("Maybe it caused by your own code if in your own Module directory.");
                Console::log($e->getTraceAsString(), 'gray');
                Process::kill($server->master_pid, SIGTERM);
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function loadAnnotations() {
        //加载phar包
        /*Console::debug("加载外部phar包中");
        $dir = DataProvider::getWorkingDir() . "/resources/package/";
        if (version_compare(SWOOLE_VERSION, "4.4.0", ">=")) Timer::clearAll();
        if (is_dir($dir)) {
            $list = scandir($dir);
            unset($list[0], $list[1]);
            foreach ($list as $v) {
                if (is_dir($dir . $v)) continue;
                if (pathinfo($dir . $v, 4) == "phar") {
                    Console::debug("加载Phar: " . $dir . $v . " 中");
                    require_once($dir . $v);
                }
            }
        }*/

        //加载各个模块的注解类，以及反射
        Console::debug("检索Module中");
        $parser = new AnnotationParser();
        $path = DataProvider::getWorkingDir() . "/src/";
        $dir = scandir($path);
        unset($dir[0], $dir[1]);
        $composer = json_decode(file_get_contents(DataProvider::getWorkingDir() . "/composer.json"), true);
        foreach ($dir as $v) {
            if (is_dir($path . "/" . $v) && isset($composer["autoload"]["psr-4"][$v . "\\"]) && !in_array($composer["autoload"]["psr-4"][$v . "\\"], $composer["extra"]["exclude_annotate"] ?? [])) {
                if (\server()->worker_id == 0)
                    Console::verbose("Add " . $v . " to register path");
                $parser->addRegisterPath(DataProvider::getWorkingDir() . "/src/" . $v . "/", $v);
            }
        }
        $parser->registerMods();
        EventManager::loadEventByParser($parser); //加载事件

        //加载自定义的全局函数
        Console::debug("加载自定义上下文中...");
        $context_class = ZMConfig::get("global", "context_class");
        if (!is_a($context_class, ContextInterface::class, true)) {
            throw new Exception("Context class must implemented from ContextInterface!");
        }

        //加载插件
        $obb_onebot = ZMConfig::get("global", "onebot") ??
            ZMConfig::get("global", "modules")["onebot"] ??
            ["status" => true, "single_bot_mode" => false, "message_level" => 99999];

        if ($obb_onebot["status"]) {
            $obj = new OnSwooleEvent();
            $obj->class = QQBot::class;
            $obj->method = 'handleByEvent';
            $obj->type = 'message';
            $obj->level = $obb_onebot["message_level"] ?? 99999;
            $obj->rule = 'connectIsQQ()';
            EventManager::addEvent(OnSwooleEvent::class, $obj);
            if ($obb_onebot["single_bot_mode"]) {
                LightCacheInside::set("connect", "conn_fd", -1);
            } else {
                LightCacheInside::set("connect", "conn_fd", -2);
            }
        }

        //TODO: 编写加载外部插件的方式
    }
}