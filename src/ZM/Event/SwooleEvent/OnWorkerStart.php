<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Error;
use Exception;
use PDO;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Process;
use Swoole\Server;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Swoole\OnMessageEvent;
use ZM\Annotation\Swoole\OnStart;
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
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Module\QQBot;
use ZM\MySQL\MySQLPool;
use ZM\Store\LightCacheInside;
use ZM\Store\MySQL\SqlPoolStorage;
use ZM\Store\Redis\ZMRedisPool;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\CronManager;
use ZM\Utils\Manager\ModuleManager;
use ZM\Utils\SignalListener;

/**
 * Class OnWorkerStart
 * @SwooleHandler("WorkerStart")
 */
class OnWorkerStart implements SwooleEvent
{
    public function onCall(Server $server, int $worker_id)
    {
        Console::debug('Calling onWorkerStart event(1)');
        if (!Framework::$argv['disable-safe-exit']) {
            SignalListener::signalWorker($server, $worker_id);
        }
        unset(Context::$context[Coroutine::getCid()]);
        if ($server->taskworker === false) {
            Framework::saveProcessState(ZM_PROCESS_WORKER, $server->worker_pid, ['worker_id' => $worker_id]);
            zm_atomic('_#worker_' . $worker_id)->set($server->worker_pid);
            if (LightCacheInside::get('wait_api', 'wait_api') !== null) {
                LightCacheInside::unset('wait_api', 'wait_api');
            }
            try {
                register_shutdown_function(function () use ($server) {
                    $error = error_get_last();
                    if (($error['type'] ?? 0) != 0) {
                        Console::error(zm_internal_errcode('E00027') . 'Internal fatal error: ' . $error['message'] . ' at ' . $error['file'] . "({$error['line']})");
                        zm_dump($error);
                    } elseif (!isset($error['type'])) {
                        return;
                    }
                    // DataProvider::saveBuffer();
                    /* @var Server $server */
                    if (server() === null) {
                        $server->shutdown();
                    } else {
                        server()->shutdown();
                    }
                });

                Console::verbose("Worker #{$server->worker_id} starting");
                Framework::$server = $server;
                // ZMBuf::resetCache(); //清空变量缓存
                // ZMBuf::set("wait_start", []); //添加队列，在workerStart运行完成前先让其他协程等待执行

                // TODO: 单独抽出来MySQL和Redis连接池
                $this->initMySQLPool();

                // 开箱即用的Redis
                $redis = ZMConfig::get('global', 'redis_config');
                if ($redis !== null && $redis['host'] != '') {
                    if (!extension_loaded('redis')) {
                        Console::error(zm_internal_errcode('E00029') . "Can not find redis extension.\n");
                    } else {
                        ZMRedisPool::init($redis);
                    }
                }

                $this->loadAnnotations(); // 加载composer资源、phar外置包、注解解析注册等
                CronManager::initCronTasks(); // 初始化定时任务
                EventManager::registerTimerTick(); // 启动计时器

                set_coroutine_params(['server' => $server, 'worker_id' => $worker_id]);
                $dispatcher = new EventDispatcher(OnStart::class);
                $dispatcher->setRuleFunction(function ($v) {
                    return server()->worker_id === $v->worker_id || $v->worker_id === -1;
                });
                $dispatcher->dispatchEvents($server, $worker_id);
                if ($dispatcher->status === EventDispatcher::STATUS_NORMAL) {
                    Console::debug('@OnStart 执行完毕');
                } else {
                    Console::warning('@OnStart 执行异常！');
                }
                Console::success('Worker #' . $worker_id . ' started');
            } catch (Exception $e) {
                Console::error('Worker加载出错！停止服务！');
                Console::error(zm_internal_errcode('E00030') . $e->getMessage() . "\n" . $e->getTraceAsString());
                Process::kill($server->master_pid, SIGTERM);
                return;
            } catch (Error $e) {
                Console::error(zm_internal_errcode('E00030') . 'PHP Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                Console::error('Maybe it caused by your own code if in your own Module directory.');
                Console::log($e->getTraceAsString(), 'gray');
                if (!Framework::$argv['watch']) { // 在热更新模式下不能退出
                    Process::kill($server->master_pid, SIGTERM);
                }
            }
        } else {
            // 这里是TaskWorker初始化的内容部分
            Framework::saveProcessState(ZM_PROCESS_TASKWORKER, $server->worker_pid, ['worker_id' => $worker_id]);
            try {
                Framework::$server = $server;
                $this->loadAnnotations();
                Console::success('TaskWorker #' . $server->worker_id . ' started');
            } catch (Exception $e) {
                Console::error('Worker加载出错！停止服务！');
                Console::error(zm_internal_errcode('E00030') . $e->getMessage() . "\n" . $e->getTraceAsString());
                Process::kill($server->master_pid, SIGTERM);
                return;
            } catch (Error $e) {
                Console::error(zm_internal_errcode('E00030') . 'PHP Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                Console::error('Maybe it caused by your own code if in your own Module directory.');
                Console::log($e->getTraceAsString(), 'gray');
                Process::kill($server->master_pid, SIGTERM);
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function loadAnnotations()
    {
        if (Framework::$instant_mode) {
            goto skip;
        }
        // 加载各个模块的注解类，以及反射
        Console::debug('Mapping annotations');
        $parser = new AnnotationParser();
        $composer = json_decode(file_get_contents(DataProvider::getSourceRootDir() . '/composer.json'), true);
        $merge = array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []);
        $exclude_annotations = array_merge($composer['extra']['exclude_annotate'] ?? [], $composer['extra']['zm']['exclude-annotation-path'] ?? []);
        foreach ($merge as $k => $v) {
            if (is_dir(DataProvider::getSourceRootDir() . '/' . $v)) {
                if (in_array($v, $exclude_annotations)) {
                    continue;
                }
                if (trim($k, '\\') == 'ZM') {
                    continue;
                }
                $parser->addRegisterPath(DataProvider::getSourceRootDir() . '/' . $v . '/', trim($k, '\\'));
            }
        }

        // 检查是否允许热加载phar模块，允许的话将遍历phar内的文件
        $plugin_enable_hotload = ZMConfig::get('global', 'module_loader')['enable_hotload'] ?? false;
        if ($plugin_enable_hotload) {
            $list = ModuleManager::getPackedModules();
            foreach ($list as $k => $v) {
                if (\server()->worker_id === 0) {
                    Console::info('Loading packed module: ' . $k);
                }
                require_once $v['phar-path'];
                $func = 'loader' . $v['generated-id'];
                $func();
                $parser->addRegisterPath('phar://' . $v['phar-path'] . '/' . $v['module-root-path'], $v['namespace']);
            }
        }

        // 检查所有的Composer模块，并加载注解
        $list = ModuleManager::getComposerModules();
        foreach ($list as $k => $v) {
            if (\server()->worker_id === 0) {
                Console::info('Loading composer module: ' . $k);
            }
            $parser->addRegisterPath($v['module-path'], $v['namespace']);
        }

        $parser->registerMods();
        EventManager::loadEventByParser($parser); // 加载事件

        skip:
        // 加载自定义的全局函数
        Console::debug('Loading context class...');
        $context_class = ZMConfig::get('global', 'context_class');
        if (!is_a($context_class, ContextInterface::class, true)) {
            throw new ZMKnownException('E00032', 'Context class must implemented from ContextInterface!');
        }
        // 加载插件
        $obb_onebot = ZMConfig::get('global', 'onebot') ??
            ZMConfig::get('global', 'modules')['onebot'] ??
            ['status' => true, 'single_bot_mode' => false, 'message_level' => 99999];
        if ($obb_onebot['status']) {
            Console::debug('OneBot support enabled, listening OneBot event(3).');
            $obj = new OnMessageEvent();
            $obj->connect_type = 'qq';
            $obj->class = QQBot::class;
            $obj->method = 'handleByEvent';
            $obj->level = $obb_onebot['message_level'] ?? 99;
            EventManager::addEvent(OnMessageEvent::class, $obj);
            if ($obb_onebot['single_bot_mode']) {
                LightCacheInside::set('connect', 'conn_fd', -1);
            } else {
                LightCacheInside::set('connect', 'conn_fd', -2);
            }
        }
    }

    private function initMySQLPool()
    {
        if (SqlPoolStorage::$sql_pool !== null) {
            SqlPoolStorage::$sql_pool->close();
            SqlPoolStorage::$sql_pool = null;
        }
        $real_conf = [];
        if (isset(ZMConfig::get('global', 'sql_config')['sql_host'])) {
            if (ZMConfig::get('global', 'sql_config')['sql_host'] != '') {
                if (\server()->worker_id === 0) {
                    Console::warning("使用 'sql_config' 配置项和 DB 数据库查询构造器进行查询数据库可能会在下一个大版本中废弃，请使用 'mysql_config' 搭配 doctrine dbal 使用！");
                    Console::warning('详见: `https://framework.zhamao.xin/`');
                }
                $origin_conf = ZMConfig::get('global', 'sql_config');
                $real_conf = [
                    'host' => $origin_conf['sql_host'],
                    'port' => $origin_conf['sql_port'],
                    'username' => $origin_conf['sql_username'],
                    'password' => $origin_conf['sql_password'],
                    'dbname' => $origin_conf['sql_database'],
                    'options' => $origin_conf['sql_options'],
                    'unix_socket' => null,
                    'charset' => 'utf8mb4',
                    'pool_size' => 64,
                ];
            }
        }
        if (isset(ZMConfig::get('global', 'mysql_config')['host'])) {
            if (ZMConfig::get('global', 'mysql_config')['host'] != '') {
                $real_conf = ZMConfig::get('global', 'mysql_config');
            }
        }
        if (!empty($real_conf)) {
            Console::info('Connecting to MySQL pool');
            ob_start();
            phpinfo(); // 这个phpinfo是有用的，不能删除
            $str = ob_get_clean();
            $str = explode("\n", $str);
            foreach ($str as $v) {
                $v = trim($v);
                if ($v == '') {
                    continue;
                }
                if (mb_strpos($v, 'API Extensions') === false) {
                    continue;
                }
                if (mb_strpos($v, 'pdo_mysql') === false) {
                    throw new DbException(zm_internal_errcode('E00028') . '未安装 mysqlnd php-mysql扩展。');
                }
            }
            SqlPoolStorage::$sql_pool = new MySQLPool(
                (new PDOConfig())
                    ->withHost($real_conf['host'])
                    ->withPort($real_conf['port'])
                // ->withUnixSocket('/tmp/mysql.sock')
                    ->withDbName($real_conf['dbname'])
                    ->withCharset($real_conf['charset'])
                    ->withUsername($real_conf['username'])
                    ->withPassword($real_conf['password'])
                    ->withOptions($real_conf['options'] ?? [PDO::ATTR_STRINGIFY_FETCHES => false])
            );
            DB::initTableList($real_conf['dbname']);
        }
    }
}
