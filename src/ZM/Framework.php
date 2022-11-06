<?php

declare(strict_types=1);

namespace ZM;

use OneBot\Driver\Driver;
use OneBot\Driver\Event\DriverInitEvent;
use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\Process\ManagerStartEvent;
use OneBot\Driver\Event\Process\ManagerStopEvent;
use OneBot\Driver\Event\Process\WorkerStartEvent;
use OneBot\Driver\Event\Process\WorkerStopEvent;
use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\Driver\Interfaces\DriverInitPolicy;
use OneBot\Driver\Swoole\SwooleDriver;
use OneBot\Driver\Workerman\Worker;
use OneBot\Driver\Workerman\WorkermanDriver;
use OneBot\Util\Singleton;
use Phar;
use ZM\Command\Server\ServerStartCommand;
use ZM\Config\ZMConfig;
use ZM\Event\EventProvider;
use ZM\Event\Listener\HttpEventListener;
use ZM\Event\Listener\ManagerEventListener;
use ZM\Event\Listener\MasterEventListener;
use ZM\Event\Listener\WorkerEventListener;
use ZM\Event\Listener\WSEventListener;
use ZM\Exception\ConfigException;
use ZM\Exception\InitException;
use ZM\Exception\ZMKnownException;
use ZM\Logger\ConsoleLogger;
use ZM\Logger\TablePrinter;
use ZM\Process\ProcessStateManager;

/**
 * 框架入口类
 * @since 3.0
 */
class Framework
{
    use Singleton;

    /** @var int 版本ID */
    public const VERSION_ID = 628;

    /** @var string 版本名称 */
    public const VERSION = '3.0.0-alpha4';

    /** @var array 传入的参数 */
    protected array $argv;

    /** @var Driver|SwooleDriver|WorkermanDriver OneBot驱动 */
    protected SwooleDriver|Driver|WorkermanDriver $driver;

    /** @var array<array<string, string>> 启动注解列表 */
    protected array $setup_annotations = [];

    /**
     * 框架初始化文件
     *
     * @param  array<string, null|bool|string> $argv 传入的参数（见 ServerStartCommand）
     * @throws InitException
     * @throws \Exception
     */
    public function __construct(array $argv = [])
    {
        // 单例化整个Framework类
        if (self::$instance !== null) {
            throw new InitException(zm_internal_errcode('E00069') . 'Initializing another Framework in one instance is not allowed!');
        }
        self::$instance = $this;

        // 初始化必需的args参数，如果没有传入的话，使用默认值
        $this->argv = empty($argv) ? ServerStartCommand::exportOptionArray() : $argv;
    }

    /**
     * @throws \Exception
     */
    public function init(): Framework
    {
        // 执行一些 Driver 前置条件的内容
        $this->initDriverPrerequisites();

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
     * @throws ZMKnownException
     */
    public function stop(int $retcode = 0)
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
     *
     * @return Driver|SwooleDriver|WorkermanDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * 在框架的 Driver 层初始化前的一些前提条件
     *
     * 1. 设置 config 读取的目录
     * 2. 初始化框架运行时的常量
     * 3. 初始化 Logger
     * 4. 初始化 EventProvider
     * 5. 设置时区，防止 Logger 时间乱跳
     * 6. 覆盖 PHP 报错样式解析
     * 7. 解析命令行参数
     * 8. 读取、解析并执行 OnSetup 注解
     *
     * @throws ConfigException
     */
    public function initDriverPrerequisites()
    {
        // 寻找配置文件目录
        if ($this->argv['config-dir'] !== null) { // 如果启动参数指定了config寻找目录，那么就在指定的寻找，不在别的地方寻找了
            $find_dir = [$this->argv['config-dir']];
            logger()->debug('使用命令参数指定的config-dir：' . $this->argv['config-dir']);
        } else { // 否则就从默认的工作目录或源码根目录寻找
            $find_dir = [WORKING_DIR . '/config', SOURCE_ROOT_DIR . '/config'];
        }
        foreach ($find_dir as $v) {
            if (is_dir($v)) {
                config()->addConfigPath($v);
                config()->setEnvironment($this->argv['env'] = ($this->argv['env'] ?? 'development'));
                $config_done = true;
                break;
            }
        }
        // 找不到的话直接崩溃，因为框架依赖全局配置文件（但其实这个错误在 3.0 开始应该永远无法执行到）
        if (!isset($config_done)) {
            echo zm_internal_errcode('E00007') . 'Global config load failed' . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n";
            exit(1);
        }

        // 初始化框架本体运行需要的常量，比如运行时间等
        require zm_dir(__DIR__ . '/../Globals/global_defines_framework.php');

        // 初始化 Logger，此处为 Master 进程第一次初始化，在后续的多进程环境下，还需要在 Worker 进程中初始化
        if (!ob_logger_registered()) { // 如果没有注册过 Logger，那么就初始化一个，在启动框架前注册的话，就不会初始化了，可替换为其他 Logger
            ob_logger_register(new ConsoleLogger($this->argv['log-level'] ?? 'info'));
        }

        // 注册自己的EventProvider
        global $ob_event_provider;
        $ob_event_provider = EventProvider::getInstance();

        // 初始化时区，默认为上海时区
        date_default_timezone_set(config('global.runtime.timezone'));

        // 注册全局错误处理器
        set_error_handler(static function ($error_no, $error_msg, $error_file, $error_line) {
            $tips = [
                E_WARNING => ['PHP Warning: ', 'warning'],
                E_NOTICE => ['PHP Notice: ', 'notice'],
                E_USER_ERROR => ['PHP Error: ', 'error'],
                E_USER_WARNING => ['PHP Warning: ', 'warning'],
                E_USER_NOTICE => ['PHP Notice: ', 'notice'],
                E_STRICT => ['PHP Strict: ', 'notice'],
                E_RECOVERABLE_ERROR => ['PHP Recoverable Error: ', 'error'],
                E_DEPRECATED => ['PHP Deprecated: ', 'notice'],
                E_USER_DEPRECATED => ['PHP User Deprecated: ', 'notice'],
            ];
            $level_tip = $tips[$error_no] ?? ['PHP Unknown: ', 'error'];
            $error = $level_tip[0] . $error_msg . ' in ' . $error_file . ' on ' . $error_line;
            logger()->{$level_tip[1]}($error);
            // 如果 return false 则错误会继续递交给 PHP 标准错误处理
            return true;
        }, E_ALL | E_STRICT);

        // 解析命令行参数
        $this->parseArgs();

        // 初始化 @OnSetup 事件
        $this->initSetupAnnotations();
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

        // 添加框架需要监听的顶层事件监听器
        // worker 事件
        ob_event_provider()->addEventListener(WorkerStartEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStart999'], 999);
        ob_event_provider()->addEventListener(WorkerStopEvent::getName(), [WorkerEventListener::getInstance(), 'onWorkerStop999'], 999);
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

        // 框架多进程依赖
        if (defined('ZM_STATE_DIR') && !is_dir(ZM_STATE_DIR)) {
            mkdir(ZM_STATE_DIR);
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
        $properties['environment'] = ZMConfig::getInstance()->getEnvironment();
        // 打印驱动
        $properties['driver'] = config('global.driver');
        // 打印logger显示等级
        $properties['log_level'] = $this->argv['log-level'] ?? config('global.log_level') ?? 'info';
        // 打印框架版本
        $properties['version'] = self::VERSION . (LOAD_MODE === 0 ? (' (build ' . ZM_VERSION_ID . ')') : '');
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
        // 打印 MySQL 连接信息
        if ((config('global.mysql_config.host') ?? '') !== '') {
            $conf = config('global', 'mysql_config');
            $properties['mysql'] = $conf['dbname'] . '@' . $conf['host'] . ':' . $conf['port'];
        }
        // 打印 Redis 连接信息
        if ((config('global', 'redis_config')['host'] ?? '') !== '') {
            $conf = config('global', 'redis_config');
            $properties['redis_pool'] = $conf['host'] . ':' . $conf['port'];
        }

        if (LOAD_MODE === 0) {
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
        // caidan
        $str = substr(sprintf('%o', fileperms(__FILE__)), -4);
        if ($str == '0777') {
            $table = ['@' => '9fX1', '!' => 'ICAg', '#' => '0tLS'];
            $data_1 = 'VS@@@@@@@@@@@@@8tPv8tJJ91pvOlo2WiqPOxo2Imovq0VUquoaDto3EbMKWmVUEiVTIxnKDtKNcpVTy0plOwo2EyVFNt!!!!!!!!!VP8XVP#############0tPvNt';
            $data_2 = $data_1 . '!!KPNtVS5sK14X!!!KPNtXT9iXIksK1@9sPvNt!!!VPusKlyp!!VPypY1jX!!!!!VUk8YF0gYKptsNbt!!!!!sUjt!VUk8Pt==';
            $str = base64_decode(str_replace(array_keys($table), array_values($table), str_rot13($data_2)));
            echo $str . PHP_EOL;
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
     * 解析 argv 参数
     */
    private function parseArgs()
    {
        foreach ($this->argv as $x => $y) {
            // 当值为 true/false 时，表示该参数为可选参数。当值为 null 时，表示该参数必定会有一个值，如果是 null，说明没指定
            if ($y === false || is_null($y)) {
                continue;
            }
            switch ($x) {
                case 'driver':      // 动态设置驱动类型
                    config()->set('global.driver', $y);
                    break;
                case 'worker-num':  // 动态设置 Worker 数量
                    config()->set('global.swoole_options.swoole_set.worker_num', intval($y));
                    config()->set('global.workerman_options.workerman_worker_num', intval($y));
                    break;
                case 'daemon':      // 启动为守护进程
                    config()->set('global.swoole_options.swoole_set.daemonize', 1);
                    Worker::$daemonize = true;
                    break;
            }
        }
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
        $json = json_decode($r, true);
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
