<?php /** @noinspection PhpUnreachableStatementInspection */

/** @noinspection PhpComposerExtensionStubsInspection */


namespace ZM\Event;


use Closure;
use Co;
use Error;
use Exception;
use PDO;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Event;
use Swoole\Process;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\OnCloseEvent;
use ZM\Annotation\Swoole\OnMessageEvent;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\Annotation\Swoole\OnPipeMessageEvent;
use ZM\Annotation\Swoole\OnRequestEvent;
use ZM\Annotation\Swoole\OnStart;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\TermColor;
use ZM\Context\Context;
use ZM\Context\ContextInterface;
use ZM\DB\DB;
use ZM\Exception\DbException;
use ZM\Exception\InterruptException;
use ZM\Framework;
use ZM\Http\Response;
use ZM\Module\QQBot;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\MySQL\SqlPoolStorage;
use ZM\Store\Redis\ZMRedisPool;
use ZM\Store\WorkerCache;
use ZM\Store\ZMBuf;
use ZM\Utils\DataProvider;
use ZM\Utils\HttpUtil;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

class ServerEventHandler
{
    /**
     * @SwooleHandler("start")
     */
    public function onStart() {
        global $terminal_id;
        $r = null;
        if ($terminal_id !== null) {
            ZMBuf::$terminal = $r = STDIN;
            Event::add($r, function () use ($r) {
                $fget = fgets($r);
                if ($fget === false) {
                    Event::del($r);
                    return;
                }
                $var = trim($fget);
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
            if (zm_atomic("_int_is_reload")->get() === 1) {
                zm_atomic("_int_is_reload")->set(0);
                ZMUtil::reload();
            } else {
                echo "\r";
                Console::warning("Server interrupted(SIGINT) on Master.");
                if ((Framework::$server->inotify ?? null) !== null)
                    /** @noinspection PhpUndefinedFieldInspection */ Event::del(Framework::$server->inotify);
                ZMUtil::stop();
            }
        });
        if (Framework::$argv["daemon"]) {
            $daemon_data = json_encode([
                "pid" => \server()->master_pid,
                "stdout" => ZMConfig::get("global")["swoole"]["log_file"]
            ], 128 | 256);
            file_put_contents(DataProvider::getWorkingDir() . "/.daemon_pid", $daemon_data);
        }
        if (Framework::$argv["watch"]) {
            if (extension_loaded('inotify')) {
                Console::warning("Enabled File watcher, do not use in production.");
                /** @noinspection PhpUndefinedFieldInspection */
                Framework::$server->inotify = $fd = inotify_init();
                $this->addWatcher(DataProvider::getWorkingDir() . "/src", $fd);
                Event::add($fd, function () use ($fd) {
                    $r = inotify_read($fd);
                    dump($r);
                    ZMUtil::reload();
                });
            } else {
                Console::warning("You have not loaded \"inotify\" extension, please install first.");
            }
        }
    }

    /**
     * @SwooleHandler("shutdown")
     */
    public function onShutdown() {
        Console::debug("正在关闭 Master 进程，pid=" . posix_getpid());
    }

    /**
     * @SwooleHandler("WorkerStop")
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStop(Server $server, $worker_id) {
        if ($worker_id == (ZMConfig::get("worker_cache")["worker"] ?? 0)) {
            LightCache::savePersistence();
        }
        Console::debug(($server->taskworker ? "Task" : "") . "Worker #$worker_id 已停止");
    }

    /**
     * @SwooleHandler("WorkerStart")
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStart(Server $server, $worker_id) {
        //if (ZMBuf::atomic("stop_signal")->get() != 0) return;
        Process::signal(SIGINT, function () use ($worker_id, $server) {
            Console::debug("正在关闭 " . ($server->taskworker ? "Task" : "") . "Worker 进程 " . Console::setColor("#" . \server()->worker_id, "gold") . TermColor::frontColor256(59) . ", pid=" . posix_getpid());
            server()->stop($worker_id);
        });
        unset(Context::$context[Coroutine::getCid()]);
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
                    if (SqlPoolStorage::$sql_pool !== null) {
                        SqlPoolStorage::$sql_pool->close();
                        SqlPoolStorage::$sql_pool = null;
                    }
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
                if ($redis !== null && $redis["host"] != "") {
                    if (!extension_loaded("redis")) Console::error("Can not find redis extension.\n");
                    else ZMRedisPool::init($redis);
                }

                $this->loadAnnotations(); //加载composer资源、phar外置包、注解解析注册等

                //echo json_encode(debug_backtrace(), 128|256);
                Console::success("Worker #" . $worker_id . " 已启动");
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
            } catch (Exception $e) {
                Console::error("Worker加载出错！停止服务！");
                Console::error($e->getMessage() . "\n" . $e->getTraceAsString());
                ZMUtil::stop();
                return;
            } catch (Error $e) {
                Console::error("PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                Console::error("Maybe it caused by your own code if in your own Module directory.");
                Console::log($e->getTraceAsString(), 'gray');
                posix_kill($server->master_pid, SIGINT);
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
                posix_kill($server->master_pid, SIGINT);
            }
        }
    }

    /**
     * @SwooleHandler("message")
     * @param $server
     * @param Frame $frame
     */
    public function onMessage($server, Frame $frame) {

        Console::debug("Calling Swoole \"message\" from fd=" . $frame->fd . ": " . TermColor::ITALIC . $frame->data . TermColor::RESET);
        unset(Context::$context[Coroutine::getCid()]);
        $conn = ManagerGM::get($frame->fd);
        set_coroutine_params(["server" => $server, "frame" => $frame, "connection" => $conn]);
        $dispatcher1 = new EventDispatcher(OnMessageEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            /** @noinspection PhpUnreachableStatementInspection */
            return ctx()->getConnection()->getName() == $v->connect_type && eval("return " . $v->getRule() . ";");
        });


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
            //$starttime = microtime(true);
            $dispatcher1->dispatchEvents($conn);
            $dispatcher->dispatchEvents($conn);
            //Console::success("Used ".round((microtime(true) - $starttime) * 1000, 3)." ms!");
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
     * @SwooleHandler("request")
     * @param $request
     * @param $response
     */
    public function onRequest(?Request $request, ?\Swoole\Http\Response $response) {
        $response = new Response($response);
        foreach (ZMConfig::get("global")["http_header"] as $k => $v) {
            $response->setHeader($k, $v);
        }
        unset(Context::$context[Co::getCid()]);
        Console::debug("Calling Swoole \"request\" event from fd=" . $request->fd);
        set_coroutine_params(["request" => $request, "response" => $response]);

        $dis1 = new EventDispatcher(OnRequestEvent::class);
        $dis1->setRuleFunction(function ($v) {
            return eval("return " . $v->getRule() . ";") ? true : false;
        });

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
            $dis1->dispatchEvents($request, $response);
            $dis->dispatchEvents($request, $response);
            if ($dis->status === EventDispatcher::STATUS_NORMAL && $dis1->status === EventDispatcher::STATUS_NORMAL) {
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
                    $dispatcher->dispatchEvent($div, null, $params, $request, $response);
                    if (is_string($dispatcher->store) && !$response->isEnd()) $response->end($dispatcher->store);
                }
            }
            if (!$response->isEnd()) {
                //Console::warning('返回了404');
                HttpUtil::responseCodePage($response, 404);
            }
        } catch (InterruptException $e) {
            // do nothing
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
            Console::error("Internal server exception (500), caused by " . get_class($e) . ": " . $e->getMessage());
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
            Console::error("Internal server error (500), caused by " . get_class($e) . ": " . $e->getMessage());
            Console::log($e->getTraceAsString(), "gray");
        }
    }

    /**
     * @SwooleHandler("open")
     * @param $server
     * @param Request $request
     */
    public function onOpen($server, Request $request) {
        Console::debug("Calling Swoole \"open\" event from fd=" . $request->fd);
        unset(Context::$context[Co::getCid()]);
        $type = strtolower($request->header["x-client-role"] ?? $request->get["type"] ?? "");
        $access_token = explode(" ", $request->header["authorization"] ?? "")[1] ?? $request->get["token"] ?? "";
        $token = ZMConfig::get("global", "access_token");
        if ($token instanceof Closure) {
            if (!$token($access_token)) {
                $server->close($request->fd);
                Console::warning("Unauthorized access_token: " . $access_token);
                return;
            }
        } elseif (is_string($token)) {
            if ($access_token !== $token && $token !== "") {
                $server->close($request->fd);
                Console::warning("Unauthorized access_token: " . $access_token);
                return;
            }
        }
        $type_conn = ManagerGM::getTypeClassName($type);
        ManagerGM::pushConnect($request->fd, $type_conn);
        $conn = ManagerGM::get($request->fd);
        set_coroutine_params(["server" => $server, "request" => $request, "connection" => $conn, "fd" => $request->fd]);
        $conn->setOption("connect_id", strval($request->header["x-self-id"] ?? ""));

        $dispatcher1 = new EventDispatcher(OnOpenEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            return ctx()->getConnection()->getName() == $v->connect_type && eval("return " . $v->getRule() . ";");
        });

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
            if ($conn->getName() === 'qq' && ZMConfig::get("global", "modules")["onebot"]["status"] === true) {
                if (ZMConfig::get("global", "modules")["onebot"]["single_bot_mode"]) {
                    LightCacheInside::set("connect", "conn_fd", $request->fd);
                }
            }
            $dispatcher1->dispatchEvents($conn);
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
     * @SwooleHandler("close")
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd) {
        unset(Context::$context[Co::getCid()]);
        $conn = ManagerGM::get($fd);
        if ($conn === null) return;
        Console::debug("Calling Swoole \"close\" event from fd=" . $fd);
        set_coroutine_params(["server" => $server, "connection" => $conn, "fd" => $fd]);

        $dispatcher1 = new EventDispatcher(OnCloseEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            return $v->connect_type == ctx()->getConnection()->getName() && eval("return " . $v->getRule() . ";");
        });

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
            if ($conn->getName() === 'qq' && ZMConfig::get("global", "modules")["onebot"]["status"] === true) {
                if (ZMConfig::get("global", "modules")["onebot"]["single_bot_mode"]) {
                    LightCacheInside::set("connect", "conn_fd", -1);
                }
            }
            $dispatcher1->dispatchEvents($conn);
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
     * @SwooleHandler("pipeMessage")
     * @param Server $server
     * @param $src_worker_id
     * @param $data
     * @throws Exception
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
            case "getWorkerCache":
                $r = WorkerCache::get($data["key"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "setWorkerCache":
                $r = WorkerCache::set($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "unsetWorkerCache":
                $r = WorkerCache::unset($data["key"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "hasKeyWorkerCache":
                $r = WorkerCache::hasKey($data["key"], $data["subkey"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "asyncAddWorkerCache":
                WorkerCache::add($data["key"], $data["value"], true);
                break;
            case "asyncSubWorkerCache":
                WorkerCache::sub($data["key"], $data["value"], true);
                break;
            case "asyncSetWorkerCache":
                WorkerCache::set($data["key"], $data["value"], true);
                break;
            case "asyncUnsetWorkerCache":
                WorkerCache::unset($data["key"], true);
                break;
            case "addWorkerCache":
                $r = WorkerCache::add($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "subWorkerCache":
                $r = WorkerCache::sub($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "returnWorkerCache":
                WorkerCache::$transfer[$data["cid"]] = $data["value"];
                zm_resume($data["cid"]);
                break;
            default:
                $dispatcher = new EventDispatcher(OnPipeMessageEvent::class);
                $dispatcher->setRuleFunction(function (OnPipeMessageEvent $v) use ($data) {
                    return $v->action == $data["action"];
                });
                $dispatcher->dispatchEvents($data);
                break;
        }
    }

    /**
     * @SwooleHandler("task")
     * @param Server|null $server
     * @param Server\Task $task
     * @return mixed
     * @noinspection PhpUnusedParameterInspection
     */
    public function onTask(?Server $server, Server\Task $task) {
        $data = $task->data;
        switch ($data["action"]) {
            case "runMethod":
                $c = $data["class"];
                $ss = new $c();
                $method = $data["method"];
                $ps = $data["params"];
                $task->finish($ss->$method(...$ps));
        }
        return null;
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
        $plugins = ZMConfig::get("global", "modules") ?? [];
        if (!isset($plugins["onebot"])) $plugins["onebot"] = ["status" => true, "single_bot_mode" => false, "message_level" => 99999];

        if ($plugins["onebot"]) {
            $obj = new OnSwooleEvent();
            $obj->class = QQBot::class;
            $obj->method = 'handle';
            $obj->type = 'message';
            $obj->level = $plugins["onebot"]["message_level"] ?? 99999;
            $obj->rule = 'connectIsQQ()';
            EventManager::addEvent(OnSwooleEvent::class, $obj);
            if ($plugins["onebot"]["single_bot_mode"]) {
                LightCacheInside::set("connect", "conn_fd", -1);
            } else {
                LightCacheInside::set("connect", "conn_fd", -2);
            }
        }

        //TODO: 编写加载外部插件的方式
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
