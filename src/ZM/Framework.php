<?php

declare(strict_types=1);

namespace ZM;

use OneBot\Driver\Driver;
use OneBot\Driver\Event\DriverInitEvent;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\Process\ManagerStartEvent;
use OneBot\Driver\Event\Process\ManagerStopEvent;
use OneBot\Driver\Event\Process\WorkerExitEvent;
use OneBot\Driver\Event\Process\WorkerStartEvent;
use OneBot\Driver\Event\Process\WorkerStopEvent;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketMessageEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\Driver\Interfaces\DriverInitPolicy;
use OneBot\Driver\Swoole\SwooleDriver;
use OneBot\Driver\Workerman\Worker;
use OneBot\Driver\Workerman\WorkermanDriver;
use OneBot\Util\Singleton;
use ZM\Bootstrap\Bootstrapper;
use ZM\Command\Server\ServerStartCommand;
use ZM\Config\RuntimePreferences;
use ZM\Container\ContainerBindingListener;
use ZM\Event\Listener\HttpEventListener;
use ZM\Event\Listener\ManagerEventListener;
use ZM\Event\Listener\MasterEventListener;
use ZM\Event\Listener\WorkerEventListener;
use ZM\Event\Listener\WSEventListener;
use ZM\Exception\SingletonViolationException;
use ZM\Exception\ZMKnownException;
use ZM\Logger\TablePrinter;
use ZM\Process\ProcessStateManager;
use ZM\Store\FileSystem;
use ZM\Utils\EasterEgg;

/**
 * 框架入口类
 * @since 3.0
 *
 * @method static Framework getInstance()
 */
class Framework
{
    use Singleton;

    /** @var int 版本ID */
    public const VERSION_ID = 724;

    /** @var string 版本名称 */
    public const VERSION = '3.2.4';

    /**
     * @var RuntimePreferences 运行时偏好（环境信息&参数）
     */
    public RuntimePreferences $runtime_preferences;

    /** @var array 传入的参数 */
    protected array $argv;

    /** @var null|Driver|SwooleDriver|WorkermanDriver OneBot驱动 */
    protected SwooleDriver|Driver|WorkermanDriver|null $driver = null;

    /** @var array<array<string, string>> 启动注解列表 */
    protected array $setup_annotations = [];

    /** @var array|string[] 框架启动前置的内容，由上到下执行 */
    protected array $bootstrappers = [
        Bootstrap\LoadConfiguration::class,         // 加载配置文件
        Bootstrap\LoadGlobalDefines::class,         // 加载框架级别的全局常量声明
        Bootstrap\RegisterLogger::class,            // 加载 Logger
        Bootstrap\HandleExceptions::class,          // 注册异常处理器
        Bootstrap\RegisterEventProvider::class,     // 绑定框架的 EventProvider 到 libob 的 Driver 上
        Bootstrap\SetInternalTimezone::class,       // 设置时区
    ];

    /**
     * 框架初始化文件
     * @throws \Exception
     */
    public function __construct()
    {
        // 单例化整个Framework类
        if (self::$instance !== null) {
            throw new SingletonViolationException(self::class);
        }
        self::$instance = $this;

        $this->runtime_preferences = new RuntimePreferences();
    }

    /**
     * 初始化框架
     *
     * @param array<string, null|bool|string> $argv 传入的参数（见 ServerStartCommand）
     *
     * @throws \Exception
     */
    public function init(array $argv = []): Framework
    {
        // TODO: discard argv
        // 初始化必需的args参数，如果没有传入的话，使用默认值
        $this->argv = empty($argv) ? ServerStartCommand::exportOptionArray() : $argv;

        // 初始化 @OnSetup 事件
        $this->initSetupAnnotations();

        // 初始化 Driver 及框架内部需要监听的事件
        $this->initDriver();

        // 初始化框架的交互以及框架部分自己要监听的事件
        $this->initFramework();

        return $this;
    }

    /**
     * 启动框架
     */
    public function start(): void
    {
        // 对多进程有效，记录当前已经加载的所有文件，最后在 Worker 进程中比较可重载的文件，用于排错
        global $zm_loaded_files;
        $zm_loaded_files = get_included_files();
        // 跑！
        $this->driver->run();
    }

    /**
     * 停止框架运行
     *
     * 未测试
     * @param  int              $retcode 退出码
     * @throws ZMKnownException
     */
    public function stop(int $retcode = 0): void
    {
        switch ($this->driver->getName()) {
            case 'swoole':
                /* @phpstan-ignore-next-line */
                $this->driver->getSwooleServer()->shutdown();
                break;
            case 'workerman':
                if (extension_loaded('posix') && isset(ProcessStateManager::getProcessState(ZM_PROCESS_MASTER)['pid'])) {
                    posix_kill(ProcessStateManager::getProcessState(ZM_PROCESS_MASTER)['pid'], SIGTERM);
                } else {
                    Worker::stopAll($retcode);
                }
                break;
        }
    }

    /**
     * 重载框架的 worker 进程，重新加载模块及代码
     *
     * 此方法仅限于 Unix 环境下的多进程模式（即存在 Worker 进程的模式）使用，Windows 环境、单进程模式使用无效
     *
     * 未测试，需要对单进程等特殊情况做判断，因为单进程等模式无法重启
     */
    public function reload()
    {
        switch ($this->driver->getName()) {
            case 'swoole':
                /* @phpstan-ignore-next-line */
                $this->driver->getSwooleServer()->reload();
                break;
            case 'workerman':
                Worker::reloadSelf();
                break;
        }
    }

    /**
     * 获取传入的参数
     */
    public function getArgv(): array
    {
        return $this->argv;
    }

    /**
     * 获取驱动
     */
    public function getDriver(): Driver
    {
        if ($this->driver === null) {
            $this->driver = new WorkermanDriver();
        }
        return $this->driver;
    }

    /**
     * 初始化驱动及相关事件
     * 实例化 Driver 对象
     *
     * @throws \Exception
     */
    public function initDriver(): void
    {
        switch ($driver = config('global.driver')) {
            case 'swoole':
                if (DIRECTORY_SEPARATOR === '\\') {
                    logger()->emergency('Windows does not support swoole driver!');
                    exit(1);
                }
                config(['global.swoole_options.driver_init_policy' => DriverInitPolicy::MULTI_PROCESS_INIT_IN_MASTER]);
                $this->driver = new SwooleDriver(config('global.swoole_options'));
                $this->driver->initDriverProtocols(config('global.servers'));
                break;
            case 'workerman':
                config(['global.workerman_options.driver_init_policy' => DriverInitPolicy::MULTI_PROCESS_INIT_IN_MASTER]);
                $this->driver = new WorkermanDriver(config('global.workerman_options'));
                $this->driver->initDriverProtocols(config('global.servers'));
                break;
            default:
                logger()->error(zm_internal_errcode('E00081') . '未知的驱动类型 ' . $driver . ' !');
                exit(1);
        }
    }

    /**
     * 初始化框架并输出一些信息
     *
     * 绑定、注册框架本身的事件到 Driver 的 EventProvider 中
     */
    public function initFramework(): void
    {
        // private-mode 模式下，不输出任何内容
        if (!$this->argv['private-mode']) {
            $this->printProperties();
            $this->printMotd();
        }

        // 注册添加容器依赖的事件
        ContainerBindingListener::listenForEvents();

        // 添加框架需要监听的顶层事件监听器
        // worker 事件
        ob_event_provider()->addEventListener(WorkerStartEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStart999'], 999);
        ob_event_provider()->addEventListener(WorkerStartEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStart1'], 1);
        ob_event_provider()->addEventListener(WorkerStopEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStop999'], 999);
        ob_event_provider()->addEventListener(WorkerStopEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStop1'], 1);
        ob_event_provider()->addEventListener(WorkerExitEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerExit'], 999);
        // Http 事件
        ob_event_provider()->addEventListener(HttpRequestEvent::getName(), [HttpEventListener::getInstance(), 'onRequest999'], 999);
        ob_event_provider()->addEventListener(HttpRequestEvent::getName(), [HttpEventListener::getInstance(), 'onRequest1'], 1);
        // manager 事件
        ob_event_provider()->addEventListener(ManagerStartEvent::getName(), [ManagerEventListener::getInstance(), 'onManagerStart'], 999);
        ob_event_provider()->addEventListener(ManagerStopEvent::getName(), [ManagerEventListener::getInstance(), 'onManagerStop'], 999);
        // master 事件
        ob_event_provider()->addEventListener(DriverInitEvent::getName(), [MasterEventListener::getInstance(), 'onMasterStart'], 999);
        // websocket 事件
        ob_event_provider()->addEventListener(WebSocketOpenEvent::getName(), [WSEventListener::getInstance(), 'onWebSocketOpen'], 999);
        ob_event_provider()->addEventListener(WebSocketCloseEvent::getName(), [WSEventListener::getInstance(), 'onWebSocketClose'], 999);
        ob_event_provider()->addEventListener(WebSocketMessageEvent::getName(), [WSEventListener::getInstance(), 'onWebSocketMessage'], 999);

        // 框架多进程依赖
        if (defined('ZM_STATE_DIR') && !is_dir(ZM_STATE_DIR)) {
            FileSystem::createDir(ZM_STATE_DIR);
        }
    }

    /**
     * 执行初始化的函数列表
     *
     * @param null|string $bootstrapper 要运行的 bootstrapper
     */
    public function bootstrap(?string $bootstrapper = null): void
    {
        if ($bootstrapper !== null) {
            (new $bootstrapper())->bootstrap($this->runtime_preferences);
            return;
        }
        foreach ($this->bootstrappers as $bootstrapper) {
            /* @var Bootstrapper $bootstrapper */
            (new $bootstrapper())->bootstrap($this->runtime_preferences);
        }
    }

    /**
     * 打印属性表格
     */
    private function printProperties(): void
    {
        $properties = [];
        // 打印工作目录
        $properties['working_dir'] = WORKING_DIR;
        // 打印环境信息
        $properties['environment'] = $this->runtime_preferences->environment();
        // 打印驱动
        $properties['driver'] = config('global.driver');
        // 打印logger显示等级
        $properties['log_level'] = $this->argv['log-level'] ?? config('global.log_level') ?? 'info';
        // 打印框架版本
        $properties['version'] = self::VERSION . (LOAD_MODE === LOAD_MODE_SRC ? (' (build ' . ZM_VERSION_ID . ')') : '');
        // 打印 PHP 版本
        $properties['php_version'] = PHP_VERSION;
        // 打印 master 进程的 pid
        $properties['master_pid'] = getmypid();
        // 打印进程模型
        if ($this->driver->getName() === 'swoole') {
            $properties['process_mode'] = 'MST1';
            ProcessStateManager::$process_mode['master'] = 1;
            if (config('global.swoole_options.swoole_server_mode') === SWOOLE_BASE) {
                $worker_num = config('global.swoole_options.swoole_set.worker_num');
                if ($worker_num === null || $worker_num === 1) {
                    $properties['process_mode'] .= 'MAN0#0';
                    ProcessStateManager::$process_mode['manager'] = 0;
                    ProcessStateManager::$process_mode['worker'] = 0;
                } elseif ($worker_num === 0) {
                    $properties['process_mode'] .= 'MAN0#' . swoole_cpu_num();
                    ProcessStateManager::$process_mode['manager'] = 0;
                    ProcessStateManager::$process_mode['worker'] = swoole_cpu_num();
                } else {
                    $properties['process_mode'] .= 'MAN0#' . ($worker = config('global.swoole_options.swoole_set.worker_num') ?? swoole_cpu_num());
                    ProcessStateManager::$process_mode['manager'] = 0;
                    ProcessStateManager::$process_mode['worker'] = $worker;
                }
            } else {
                $worker = config('global.swoole_options.swoole_set.worker_num') === 0 ? swoole_cpu_num() : config('global.swoole_options.swoole_set.worker_num') ?? swoole_cpu_num();
                $properties['process_mode'] .= 'MAN1#' . $worker;
                ProcessStateManager::$process_mode['manager'] = 1;
                ProcessStateManager::$process_mode['worker'] = $worker;
            }
        } elseif ($this->driver->getName() === 'workerman') {
            $properties['process_mode'] = 'MST1';
            ProcessStateManager::$process_mode['master'] = 1;
            $worker_num = config('global.workerman_options.workerman_worker_num');
            if (DIRECTORY_SEPARATOR === '\\') {
                $properties['process_mode'] .= '#0';
                ProcessStateManager::$process_mode['manager'] = 0;
                ProcessStateManager::$process_mode['worker'] = 0;
            } else {
                $worker_num = $worker_num === 0 ? 1 : ($worker_num ?? 1);
                $properties['process_mode'] .= '#' . $worker_num;
                ProcessStateManager::$process_mode['manager'] = 0;
                ProcessStateManager::$process_mode['worker'] = $worker_num;
            }
        }
        // 打印监听端口
        foreach (config('global.servers') as $k => $v) {
            $properties['listen_' . $k] = $v['type'] . '://' . $v['host'] . ':' . $v['port'];
        }
        // 打印 database 连接信息
        foreach (config('global.database') as $name => $db) {
            if (!$db['enable']) {
                continue;
            }
            $properties['db[' . $name . ']'] = match ($db['type']) {
                'sqlite' => $db['type'] . '://' . $db['dbname'],
                'mysql', 'pgsql' => $db['type'] . '://' . $db['host'] . ':' . $db['port'] . '/' . $db['dbname'],
                default => '未知数据库类型',
            };
        }
        // 打印 redis 连接信息
        foreach (config('global.redis') as $name => $redis) {
            if ($redis['enable']) {
                $properties['redis[' . $name . ']'] = $redis['host'] . ':' . $redis['port'];
            }
        }
        if (LOAD_MODE === LOAD_MODE_SRC) {
            logger()->info('框架正以源码模式启动');
        }
        logger()->debug('Starting framework with properties: ' . json_encode($properties, JSON_UNESCAPED_SLASHES));
        $this->translateProperties($properties);
        $printer = new TablePrinter($properties);
        $printer->setValueColor('random')->printAll();
    }

    /**
     * 翻译属性
     */
    private function translateProperties(array &$properties): void
    {
        $translations = [
            'working_dir' => '工作目录',
            'worker' => '工作进程数',
            'environment' => '环境类型',
            'log_level' => '日志级别',
            'version' => '框架版本',
            'master_pid' => '主进程 PID',
            'app_version' => '应用版本',
            'task_worker' => '任务进程数',
            'mysql_pool' => '数据库',
            'mysql' => '数据库',
            'redis_pool' => 'Redis',
            'php_version' => 'PHP 版本',
            'process_mode' => '进程模型',
            'driver' => '驱动类型',
            'listen_0' => '监听端口1',
            'listen_1' => '监听端口2',
            'listen_2' => '监听端口3',
            'listen_3' => '监听端口4',
        ];
        // 更换数组键名
        foreach ($properties as $k => $v) {
            if (isset($translations[$k])) {
                $keys = array_keys($properties);
                $keys[array_search($k, $keys, false)] = $translations[$k];
                $properties = ($t = array_combine($keys, $properties)) ? $t : $properties;
            }
        }
    }

    /**
     * 打印 MOTD
     */
    private function printMotd(): void
    {
        // 先获取终端宽度，防止超过边界换行
        $tty_width = (new TablePrinter([]))->fetchTerminalSize();
        if ($s = EasterEgg::checkFrameworkPermissionCall()) {
            echo $s;
            return;
        }
        // 从源码目录、框架本身的初始目录寻找 MOTD 文件
        if (file_exists(SOURCE_ROOT_DIR . '/config/motd.txt')) {
            $motd = file_get_contents(SOURCE_ROOT_DIR . '/config/motd.txt');
        } else {
            $motd = file_get_contents(FRAMEWORK_ROOT_DIR . '/config/motd.txt');
        }
        $motd = explode("\n", $motd);
        foreach ($motd as $k => $v) {
            $motd[$k] = substr($v, 0, $tty_width);
        }
        $motd = implode("\n", $motd);
        echo $motd;
    }

    /**
     * 初始化 OnSetup 注解
     */
    private function initSetupAnnotations(): void
    {
        if (\Phar::running() !== '') {
            // 在 Phar 下，不需要新启动进程了，因为 Phar 没办法重载，自然不需要考虑多进程的加载 reload 问题
            require FRAMEWORK_ROOT_DIR . '/src/Globals/script_setup_loader.php';
            $r = _zm_setup_loader();
            $result_code = 0;
        } else {
            // 其他情况下，需要启动一个新的进程执行结果后退出内存中加载的内容，以便重载模块
            $r = exec(PHP_BINARY . ' ' . FRAMEWORK_ROOT_DIR . '/src/Globals/script_setup_loader.php', $output, $result_code);
        }
        if ($result_code !== 0) {
            logger()->emergency('代码解析错误！');
            exit(1);
        }
        $json = json_decode($r, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($json)) {
            logger()->error(zm_internal_errcode('E00012') . '解析 @OnSetup 时发生错误，请检查代码！');
            return;
        }
        $this->setup_annotations = $json['setup'];
        foreach (($this->setup_annotations) as $v) {
            logger()->debug('Calling @OnSetup: ' . $v['class']);
            $cname = $v['class'];
            $c = new $cname();
            $method = $v['method'];
            $c->{$method}();
        }
    }
}
