<?php /** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event;


use Co;
use Error;
use Exception;
use PDO;
use ReflectionException;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\OnWorkerStart;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use ZM\Annotation\Swoole\HandleEvent;
use ZM\Console\TermColor;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\DB\DB;
use ZM\Exception\DbException;
use ZM\Framework;
use ZM\Http\Response;
use ZM\Module\QQBot;
use ZM\Store\MySQL\SqlPoolStorage;
use ZM\Store\Redis\ZMRedisPool;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use ZM\Utils\HttpUtil;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

class ServerEventHandler
{
    /**
     * @HandleEvent("start")
     */
    public function onStart() {
        global $terminal_id;
        $r = null;
        if ($terminal_id !== null) {
            ZMBuf::$terminal = $r = STDIN;
            Event::add($r, function () use ($r) {
                $var = trim(fgets($r));
                try {
                    Terminal::executeCommand($var, $r);
                } catch (Exception $e) {
                    Console::error("Uncaught exception " . get_class($e) . ": " . $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")");
                } catch (Error $e) {
                    Console::error("Uncaught error " . get_class($e) . ": " . $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")");
                }
            });
        }
        Process::signal(SIGINT, function () use ($r) {
            echo "\r";
            Console::warning("Server interrupted by keyboard on Master.");
            if ((Framework::$server->inotify ?? null) !== null)
                /** @noinspection PhpUndefinedFieldInspection */ Event::del(Framework::$server->inotify);
            ZMUtil::stop();
        });
        if (Framework::$argv["watch"]) {
            if (extension_loaded('inotify')) {
                Console::warning("Enabled File watcher, do not use in production.");
                /** @noinspection PhpUndefinedFieldInspection */
                Framework::$server->inotify = $fd = inotify_init();
                $this->addWatcher(DataProvider::getWorkingDir() . "/src", $fd);
                Event::add($fd, function () use ($fd) {
                    $r = inotify_read($fd);
                    var_dump($r);
                    ZMUtil::reload();
                });
            } else {
                Console::warning("You have not loaded inotify extension.");
            }
        }
    }

    /**
     * @HandleEvent("shutdown")
     */
    public function onShutdown() {
        Console::debug("正在关闭 Master 进程，pid=" . posix_getpid());
    }

    /**
     * @HandleEvent("WorkerStop")
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStop(Server $server, $worker_id) {
        Console::debug(($server->taskworker ? "Task" : "") . "Worker #$worker_id 已停止");
    }

    /**
     * @HandleEvent("WorkerStart")
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStart(Server $server, $worker_id) {
        //if (ZMBuf::atomic("stop_signal")->get() != 0) return;
        Process::signal(SIGINT, function () use ($worker_id, $server) {
            Console::debug("正在关闭 " . ($server->taskworker ? "Task" : "") . "Worker 进程 " . Console::setColor("#" . \server()->worker_id, "gold") . TermColor::frontColor256(59) . ", pid=" . posix_getpid());
            server()->stop($worker_id);
        });
        unset(Context::$context[Co::getCid()]);
        if ($server->taskworker === false) {
            try {
                register_shutdown_function(function () use ($server) {
                    $error = error_get_last();
                    if ($error["type"] != 0) {
                        Console::error("Internal fatal error: " . $error["message"] . " at " . $error["file"] . "({$error["line"]})");
                    }
                    //DataProvider::saveBuffer();
                    /** @var Server $server */
                    if (server() === null) $server->shutdown();
                    else server()->shutdown();
                });

                Console::info("Worker #{$server->worker_id} 启动中");
                Framework::$server = $server;
                //ZMBuf::resetCache(); //清空变量缓存
                //ZMBuf::set("wait_start", []); //添加队列，在workerStart运行完成前先让其他协程等待执行
                foreach ($server->connections as $v) {
                    $server->close($v);
                }
                if (SqlPoolStorage::$sql_pool !== null) {
                    SqlPoolStorage::$sql_pool->close();
                    SqlPoolStorage::$sql_pool = null;
                }

                // 这里执行的是只需要执行一遍的代码，比如终端监听器和键盘监听器
                /*if ($server->worker_id === 0) {
                    global $terminal_id;
                    if ($terminal_id !== null)
                        go(function () {
                            while (true) {
                                $r = server()->process->exportSocket();
                                $result = $r->recv();
                                try {
                                    if (!Terminal::executeCommand($result)) {
                                        //if ($result == "stop" || $result == "reload" || $result == "r") {
                                        //echo "Stopped.\n";
                                        break;
                                    }
                                } catch (Exception $e) {
                                    Console::error($e->getMessage());
                                } catch (Error $e) {
                                    Console::error($e->getMessage());
                                }
                            }
                        });
                }*/
                //TODO: 单独抽出来MySQL和Redis连接池
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
                if($redis !== null && $redis["host"] != "") {
                    if (!extension_loaded("redis")) Console::error("Can not find redis extension.\n");
                    else ZMRedisPool::init($redis);
                }

                $this->loadAnnotations(); //加载composer资源、phar外置包、注解解析注册等

                //echo json_encode(debug_backtrace(), 128|256);
                Console::success("Worker #" . $worker_id . " 已启动");
                EventManager::registerTimerTick(); //启动计时器
                //ZMBuf::unsetCache("wait_start");
                set_coroutine_params(["server" => $server, "worker_id" => $worker_id]);
                $dispatcher = new EventDispatcher(OnWorkerStart::class);
                $dispatcher->setRuleFunction(function ($v) {
                    return server()->worker_id === $v->worker_id || $v->worker_id === -1;
                });
                $dispatcher->dispatchEvents($server, $worker_id);
                Console::debug("@OnWorkerStart 执行完毕");
            } catch (Exception $e) {
                Console::error("Worker加载出错！停止服务！");
                Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                ZMUtil::stop();
                return;
            } catch (Error $e) {
                Console::error("PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                Console::error("Maybe it caused by your own code if in your own Module directory.");
                Console::log($e->getTraceAsString(), 'gray');
                ZMUtil::stop();
            }
        } else {
            // 这里是TaskWorker初始化的内容部分
            try {
                Framework::$server = $server;
                $this->loadAnnotations();
                Console::debug("TaskWorker #" . $server->worker_id . " 已启动");
            } catch (Exception $e) {
                Console::error("Worker加载出错！停止服务！");
                Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                ZMUtil::stop();
                return;
            } catch (Error $e) {
                Console::error("PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                Console::error("Maybe it caused by your own code if in your own Module directory.");
                Console::log($e->getTraceAsString(), 'gray');
                ZMUtil::stop();
            }
        }
    }

    /**
     * @HandleEvent("message")
     * @param $server
     * @param Frame $frame
     */
    public function onMessage($server, Frame $frame) {

        Console::debug("Calling Swoole \"message\" from fd=" . $frame->fd.": ".TermColor::ITALIC.$frame->data.TermColor::RESET);
        unset(Context::$context[Co::getCid()]);
        $conn = ManagerGM::get($frame->fd);
        set_coroutine_params(["server" => $server, "frame" => $frame, "connection" => $conn]);
        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'message';
            } else {
                /** @noinspection PhpUnreachableStatementInspection
                 * @noinspection RedundantSuppression
                 */
                if (strtolower($v->type) == 'message' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });
        try {
            $starttime = microtime(true);
            $dispatcher->dispatchEvents($conn);
            Console::success("Used ".round((microtime(true) - $starttime) * 1000, 3)." ms!");
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught exception " . get_class($e) . " when calling \"message\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught Error " . get_class($e) . " when calling \"message\": " . $error_msg);
            Console::trace();
        }

    }

    /**
     * @HandleEvent("request")
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response) {
        $response = new Response($response);
        unset(Context::$context[Co::getCid()]);
        Console::debug("Calling Swoole \"request\" event from fd=" . $request->fd);
        set_coroutine_params(["request" => $request, "response" => $response]);

        $dis = new EventDispatcher(OnSwooleEvent::class);
        $dis->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'request';
            } else {
                /** @noinspection PhpUnreachableStatementInspection */
                if (strtolower($v->type) == 'request' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });

        try {
            $no_interrupt = $dis->dispatchEvents($request, $response);
            if ($no_interrupt !== null) {
                $result = HttpUtil::parseUri($request, $response, $request->server["request_uri"], $node, $params);
                if ($result === true) {
                    ctx()->setCache("params", $params);
                    $dispatcher = new EventDispatcher(RequestMapping::class);
                    $div = new RequestMapping();
                    $div->route = $node["route"];
                    $div->params = $params;
                    $div->method = $node["method"];
                    $div->request_method = $node["request_method"];
                    $div->class = $node["class"];
                    //Console::success("正在执行路由：".$node["method"]);
                    $r = $dispatcher->dispatchEvent($div, null, $params, $request, $response);
                    if (is_string($r) && !$response->isEnd()) $response->end($r);
                }
            }
            if (!$response->isEnd()) {
                //Console::warning('返回了404');
                HttpUtil::responseCodePage($response, 404);
            }
        } catch (Exception $e) {
            $response->status(500);
            Console::info($request->server["remote_addr"] . ":" . $request->server["remote_port"] .
                " [" . $response->getStatusCode() . "] " . $request->server["request_uri"]
            );
            if (!$response->isEnd()) {
                if (ZMConfig::get("global", "debug_mode"))
                    $response->end("Internal server exception: " . $e->getMessage());
                else
                    $response->end("Internal server error.");
            }
            Console::error("Internal server exception (500), caused by " . get_class($e).": ".$e->getMessage());
            Console::log($e->getTraceAsString(), "gray");
        } catch (Error $e) {
            $response->status(500);
            Console::info($request->server["remote_addr"] . ":" . $request->server["remote_port"] .
                " [" . $response->getStatusCode() . "] " . $request->server["request_uri"]
            );
            if (!$response->isEnd()) {
                $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                if (ZMConfig::get("global", "debug_mode"))
                    $response->end("Internal server error: " . $error_msg);
                else
                    $response->end("Internal server error.");
            }
            Console::error("Internal server error (500), caused by " . get_class($e));
            Console::log($e->getTraceAsString(), "gray");
        }
    }

    /**
     * @HandleEvent("open")
     * @param $server
     * @param Request $request
     */
    public function onOpen($server, Request $request) {
        Console::debug("Calling Swoole \"open\" event from fd=" . $request->fd);
        unset(Context::$context[Co::getCid()]);
        $type = strtolower($request->get["type"] ?? $request->header["x-client-role"] ?? "");
        $type_conn = ManagerGM::getTypeClassName($type);
        ManagerGM::pushConnect($request->fd, $type_conn);
        $conn = ManagerGM::get($request->fd);
        set_coroutine_params(["server" => $server, "request" => $request, "connection" => $conn, "fd" => $request->fd]);
        $conn->setOption("connect_id", strval($request->header["x-self-id"]) ?? "");
        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'open';
            } else {
                /** @noinspection PhpUnreachableStatementInspection */
                if (strtolower($v->type) == 'open' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });
        try {
            $dispatcher->dispatchEvents($conn);
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught exception " . get_class($e) . " when calling \"open\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught Error " . get_class($e) . " when calling \"open\": " . $error_msg);
            Console::trace();
        }
        //EventHandler::callSwooleEvent("open", $server, $request);
    }

    /**
     * @HandleEvent("close")
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd) {
        unset(Context::$context[Co::getCid()]);
        $conn = ManagerGM::get($fd);
        if ($conn === null) return;
        Console::debug("Calling Swoole \"close\" event from fd=" . $fd);
        set_coroutine_params(["server" => $server, "connection" => $conn, "fd" => $fd]);
        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'close';
            } else {
                /** @noinspection PhpUnreachableStatementInspection */
                if (strtolower($v->type) == 'close' && eval("return " . $v->getRule() . ";")) return true;
                else return false;
            }
        });
        try {
            $dispatcher->dispatchEvents($conn);
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught exception " . get_class($e) . " when calling \"close\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error("Uncaught Error " . get_class($e) . " when calling \"close\": " . $error_msg);
            Console::trace();
        }
        ManagerGM::popConnect($fd);
    }

    /**
     * @HandleEvent("pipeMessage")
     * @param $server
     * @param $src_worker_id
     * @param $data
     */
    public function onPipeMessage(Server $server, $src_worker_id, $data) {
        //var_dump($data, $server->worker_id);
        //unset(Context::$context[Co::getCid()]);
        $data = json_decode($data, true);
        switch ($data["action"] ?? '') {
            case "resume_ws_message":
                $obj = $data["data"];
                Co::resume($obj["coroutine"]);
                break;
            case "stop":
                Console::verbose('正在清理 #' . $server->worker_id . ' 的计时器');
                Timer::clearAll();
                break;
            case "terminate":
                $server->stop();
                break;
            case 'echo':
                Console::success('接收到来自 #' . $src_worker_id . ' 的消息');
                break;
            case 'send':
                $server->sendMessage(json_encode(["action" => "echo"]), $data["target"]);
                break;
            default:
                echo $data . PHP_EOL;
        }
    }

    /**
     * @HandleEvent("task")
     */
    public function onTask() {
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
        $parser->addRegisterPath(DataProvider::getWorkingDir() . "/src/Module/", "Module");
        $parser->registerMods();
        EventManager::loadEventByParser($parser); //加载事件

        //加载自定义的全局函数
        Console::debug("加载自定义上下文中...");
        $context_class = ZMConfig::get("global", "context_class");
        if (!is_a($context_class, ContextInterface::class, true)) {
            throw new Exception("Context class must implemented from ContextInterface!");
        }

        //加载插件
        $plugins = ZMConfig::get("global", "modules") ?? [];
        if (!isset($plugins["qqbot"])) $plugins["qqbot"] = true;

        if ($plugins["qqbot"]) {
            $obj = new OnSwooleEvent();
            $obj->class = QQBot::class;
            $obj->method = 'handle';
            $obj->type = 'message';
            $obj->level = 99999;
            $obj->rule = 'connectIsQQ()';
            EventManager::addEvent(OnSwooleEvent::class, $obj);
        }

        //TODO: 编写加载外部插件的方式
        //$this->loadExternalModules();
    }

    private function addWatcher($maindir, $fd) {
        $dir = scandir($maindir);
        unset($dir[0], $dir[1]);
        foreach ($dir as $subdir) {
            if (is_dir($maindir . "/" . $subdir)) {
                Console::debug("添加监听目录：" . $maindir . "/" . $subdir);
                inotify_add_watch($fd, $maindir . "/" . $subdir, IN_ATTRIB | IN_ISDIR);
                $this->addWatcher($maindir . "/" . $subdir, $fd);
            }
        }
    }
}
