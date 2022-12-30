<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Process\ProcessManager;
use OneBot\Util\Singleton;
use ZM\Annotation\AnnotationHandler;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\Init;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Plugin\CommandManualPlugin;
use ZM\Plugin\OneBot12Adapter;
use ZM\Plugin\PluginManager;
use ZM\Process\ProcessStateManager;
use ZM\Store\Database\DBException;
use ZM\Store\Database\DBPool;
use ZM\Store\FileSystem;
use ZM\Utils\ZMUtil;

class WorkerEventListener
{
    use Singleton;

    /**
     * Driver 的 Worker 进程启动后执行的事件
     *
     * @throws \Throwable
     */
    public function onWorkerStart999(): void
    {
        // 自注册一下，刷新当前进程的logger进程banner
        ob_logger_register(ob_logger());

        Adaptive::initWithDriver(Framework::getInstance()->getDriver());

        // 如果没有引入参数disable-safe-exit，则监听 Ctrl+C
        if (!Framework::getInstance()->getArgv()['disable-safe-exit'] && PHP_OS_FAMILY !== 'Windows') {
            SignalListener::getInstance()->signalWorker();
        }

        // Windows 环境下，为了监听 Ctrl+C，只能开启终端输入
        if (PHP_OS_FAMILY === 'Windows') {
            logger()->debug('监听Windows的键盘输入');
            sapi_windows_set_ctrl_handler([SignalListener::getInstance(), 'signalWindowsCtrlC']);
        }

        // 设置 Worker 进程的状态和 ID 等信息
        if (($name = Framework::getInstance()->getDriver()->getName()) === 'swoole') {
            /* @phpstan-ignore-next-line */
            $server = Framework::getInstance()->getDriver()->getSwooleServer();
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, $server->worker_pid, ['worker_id' => $server->worker_id]);
        } elseif ($name === 'workerman') {
            ProcessStateManager::saveProcessState(ZM_PROCESS_WORKER, getmypid(), ['worker_id' => ProcessManager::getProcessId()]);
        }

        // 打印进程ID
        if (Framework::getInstance()->getArgv()['print-process-pid'] && ProcessManager::getProcessId() === 0) {
            logger()->info("MASTER:\t" . ProcessStateManager::getProcessState(ZM_PROCESS_MASTER)['pid']);
            if (ProcessStateManager::$process_mode['manager'] > 0) {
                logger()->info("MANAGER:\t" . ProcessStateManager::getProcessState(ZM_PROCESS_MANAGER));
            }
        }
        if (Framework::getInstance()->getArgv()['print-process-pid']) {
            $i = ProcessManager::getProcessId();
            logger()->info('WORKER#' . $i . ":\t" . ProcessStateManager::getProcessState(ZM_PROCESS_WORKER, $i));
        }

        // 注册 Worker 进程遇到退出时的回调，安全退出
        register_shutdown_function(function () {
            $error = error_get_last();
            // 下面这段代码的作用就是，不是错误引发的退出时照常退出即可
            if (($error['type'] ?? 0) != 0) {
                logger()->emergency(zm_internal_errcode('E00027') . 'Internal fatal error: ' . $error['message'] . ' at ' . $error['file'] . "({$error['line']})");
            } elseif (!isset($error['type'])) {
                return;
            }
            Framework::getInstance()->stop();
        });

        // 注册各种池子
        $this->initConnectionPool();

        // 加载用户代码资源
        $this->initUserPlugins();

        // handle @Init annotation
        Adaptive::getCoroutine()->create(function () {
            $this->dispatchInit();
        });
        // 回显 debug 日志：进程占用的内存
        $memory_total = memory_get_usage() / 1024 / 1024;
        logger()->debug('Worker process used ' . round($memory_total, 3) . ' MB');
    }

    public function onWorkerStart1(): void
    {
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' started');
    }

    /**
     * @throws ZMKnownException
     */
    public function onWorkerStop999(): void
    {
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' stopping');
        if (DIRECTORY_SEPARATOR !== '\\') {
            ProcessStateManager::removeProcessState(ZM_PROCESS_WORKER, ProcessManager::getProcessId());
        }
        // 清空 MySQL 的连接池
        foreach (DBPool::getAllPools() as $name => $pool) {
            DBPool::destroyPool($name);
        }
    }

    public function onWorkerStop1(): void
    {
        logger()->debug('Worker #' . ProcessManager::getProcessId() . ' stopped');
    }

    /**
     * 加载用户代码资源，包括普通插件、单文件插件、Composer 插件等
     * @throws \Throwable
     */
    private function initUserPlugins(): void
    {
        logger()->debug('Loading user sources');

        // 首先先加载 source 模式的代码，相当于内部模块，不算插件的一种
        $parser = new AnnotationParser();
        $composer = ZMUtil::getComposerMetadata();
        // 合并 dev 和 非 dev 的 psr-4 加载目录
        $merge_psr4 = array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []);
        // 排除 composer.json 中指定需要排除的目录
        $excludes = $composer['extra']['zm']['exclude-annotation-path'] ?? [];
        foreach ($merge_psr4 as $k => $v) {
            // 如果在排除表就排除，否则就解析注解
            if (is_dir(SOURCE_ROOT_DIR . '/' . $v) && !in_array($v, $excludes)) {
                // 添加解析路径，对应Base命名空间也贴出来
                $parser->addRegisterPath(SOURCE_ROOT_DIR . '/' . $v . '/', trim($k, '\\'));
            }
        }

        // 首先加载内置插件
        $native_plugins = config('global.native_plugin');
        foreach ($native_plugins as $name => $enable) {
            if (!$enable) {
                continue;
            }
            match ($name) {
                'onebot12' => PluginManager::addPlugin(['name' => $name, 'internal' => true, 'object' => new OneBot12Adapter(parser: $parser)]),
                'onebot12-ban-other-ws' => PluginManager::addPlugin(['name' => $name, 'internal' => true, 'object' => new OneBot12Adapter(submodule: $name)]),
                'command-manual' => PluginManager::addPlugin(['name' => $name, 'internal' => true, 'object' => new CommandManualPlugin($parser)]),
            };
        }

        // 然后加载插件目录的插件
        if (config('global.plugin.enable')) {
            $load_dir = config('global.plugin.load_dir');
            if (empty($load_dir)) {
                $load_dir = SOURCE_ROOT_DIR . '/plugins';
            } elseif (FileSystem::isRelativePath($load_dir)) {
                $load_dir = SOURCE_ROOT_DIR . '/' . $load_dir;
            }
            $load_dir = zm_dir($load_dir);

            $count = PluginManager::addPluginsFromDir($load_dir);
            logger()->info('Loaded ' . $count . ' user plugins');

            // 启用并初始化插件
            PluginManager::enablePlugins($parser);
        }

        // 解析所有注册路径的文件，获取注解
        $parser->parseAll();
        // 将Parser解析后的注解注册到全局的 AnnotationMap
        AnnotationMap::loadAnnotationByParser($parser);
        // 排序所有的
        AnnotationMap::sortAnnotationList();
    }

    /**
     * 分发调用 Init 注解
     *
     * @throws \Throwable
     */
    private function dispatchInit(): void
    {
        $handler = new AnnotationHandler(Init::class);
        $handler->setRuleCallback(fn (Init $anno) => $anno->worker === -1 || $anno->worker === ProcessManager::getProcessId());
        $handler->handleAll();
    }

    /**
     * 初始化各种连接池
     *
     * TODO：未来新增其他db的连接池
     *
     * @throws DBException
     */
    private function initConnectionPool(): void
    {
        // 清空 MySQL 的连接池
        foreach (DBPool::getAllPools() as $name => $pool) {
            DBPool::destroyPool($name);
        }

        // 读取 MySQL 配置文件
        $conf = config('global.database');
        // 如果有多个数据库连接，则遍历
        foreach ($conf as $name => $conn_conf) {
            if (($conn_conf['enable'] ?? true) !== false) {
                DBPool::create($name, $conn_conf);
            }
        }
    }
}
