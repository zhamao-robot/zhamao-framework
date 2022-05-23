<?php

declare(strict_types=1);

namespace ZM;

use Doctrine\Common\Annotations\AnnotationReader;
use Error;
use Exception;
use InvalidArgumentException;
use Phar;
use ReflectionClass;
use ReflectionException;
use Swoole\Runtime;
use Swoole\Server\Port;
use Swoole\WebSocket\Server;
use Throwable;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Exception\ConfigException;
use ZM\Logger\TablePrinter;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\DataProvider;
use ZM\Utils\Manager\ProcessManager;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

class Framework
{
    public const VERSION_ID = 605;

    public const VERSION = '3.0.0-alpha1';

    /**
     * 框架运行的参数
     *
     * @var array
     */
    public static $argv;

    /**
     * 通信服务器实例
     *
     * @var Server
     */
    public static $server;

    /**
     * 框架加载的文件
     *
     * @var string[]
     */
    public static $loaded_files = [];

    /**
     * 是否为单文件模式
     *
     * @var bool
     */
    public static $instant_mode = false;

    /**
     * Swoole 服务端配置
     *
     * @var null|array
     */
    private $swoole_server_config;

    /**
     * @var array
     */
    private $setup_events = [];

    /**
     * 创建一个新的框架实例
     *
     * @param  array           $args         运行参数
     * @param  bool            $instant_mode 是否为单文件模式
     * @throws ConfigException
     */
    public function __construct(array $args = [], bool $instant_mode = false)
    {
        self::$instant_mode = $instant_mode;
        self::$argv = $args;
        Runtime::enableCoroutine(false);

        // 初始化配置
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args['env'] ?? 'development');
        if (ZMConfig::get('global') === false) {
            echo zm_internal_errcode('E00007') . 'Global config load failed: ' . ZMConfig::$last_error . "\nError path: " . DataProvider::getSourceRootDir() . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n";
            exit(1);
        }

        // 定义常量
        require_once 'global_defines.php';

        // 确保目录存在
        DataProvider::createIfNotExists(ZMConfig::get('global', 'zm_data'));
        DataProvider::createIfNotExists(ZMConfig::get('global', 'config_dir'));
        DataProvider::createIfNotExists(ZMConfig::get('global', 'crash_dir'));

        // 初始化连接池？
        try {
            ManagerGM::init(ZMConfig::get('global', 'swoole')['max_connection'] ?? 2048, 0.5, [
                [
                    'key' => 'connect_id',
                    'type' => 'string',
                    'size' => 30,
                ],
                [
                    'key' => 'type',
                    'type' => 'int',
                ],
            ]);
        } catch (ConnectionManager\TableException $e) {
            logger()->emergency(zm_internal_errcode('E00008') . $e->getMessage());
            exit(1);
        }

        try {
            // 初始化日志
            Console::init(
                ZMConfig::get('global', 'info_level') ?? 2,
                self::$server,
                $args['log-theme'] ?? 'default',
                ($o = ZMConfig::get('console_color')) === false ? [] : $o
            );
            // 是否同步输出到文件
            if ((ZMConfig::get('global', 'runtime')['save_console_log_file'] ?? false) !== false) {
                Console::setOutputFile(ZMConfig::get('global', 'runtime')['save_console_log_file']);
            }

            // 设置默认时区
            $timezone = ZMConfig::get('global', 'timezone') ?? 'Asia/Shanghai';
            date_default_timezone_set($timezone);

            // 读取 Swoole 配置
            $this->swoole_server_config = ZMConfig::get('global', 'swoole');
            $this->swoole_server_config['log_level'] = SWOOLE_LOG_DEBUG;

            // 是否启用远程终端
            $remote_terminal = ZMConfig::get('global', 'remote_terminal')['status'] ?? false;

            // 加载服务器事件
            if (!$instant_mode) {
                $this->loadServerEvents();
            }

            // 解析命令行参数
            [$coroutine_mode, $terminal_id, $remote_terminal] = $this->parseCliArgs(self::$argv, compact('remote_terminal'));

            // 设置默认最长等待时间
            if (!isset($this->swoole_server_config['max_wait_time'])) {
                $this->swoole_server_config['max_wait_time'] = 5;
            }
            // 设置最大 worker 进程数
            $worker = $this->swoole_server_config['worker_num'] ?? swoole_cpu_num();
            define('ZM_WORKER_NUM', $worker);

            // 初始化原子计数器
            ZMAtomic::init();

            // 非静默模式下打印启动信息
            if (!self::$argv['private-mode']) {
                $this->printProperties($remote_terminal, $args);
            }

            // 预览模式则直接提出
            if ($args['preview'] ?? false) {
                exit();
            }

            // 初始化服务器
            self::$server = new Server(
                ZMConfig::get('global', 'host'),
                ZMConfig::get('global', 'port'),
                ZMConfig::get('global', 'runtime')['swoole_server_mode'] ?? SWOOLE_PROCESS
            );

            // 监听远程终端
            if ($remote_terminal) {
                $conf = ZMConfig::get('global', 'remote_terminal') ?? [
                    'status' => true,
                    'host' => '127.0.0.1',
                    'port' => 20002,
                    'token' => '',
                ];

                $welcome_msg = capture_output([logger('trm'), 'info'], ['欢迎使用炸毛远程终端！输入 `help` 查看帮助！']);
                $motd = capture_output(function () {
                    $this->printMotd();
                });

                /** @var Port $port */
                $port = self::$server->listen($conf['host'], $conf['port'], SWOOLE_SOCK_TCP);
                $port->set([
                    'open_http_protocol' => false,
                ]);
                $port->on('connect', function (\Swoole\Server $serv, $fd) use ($welcome_msg, $conf, $motd) {
                    ManagerGM::pushConnect($fd, 'terminal');
                    // 推送欢迎信息
                    $serv->send($fd, $motd);
                    // 要求输入令牌
                    if (!empty($conf['token'])) {
                        $serv->send($fd, '请输入令牌：');
                    } else {
                        $serv->send($fd, $welcome_msg . "\n>>> ");
                    }
                });

                $port->on('receive', function ($serv, $fd, $reactor_id, $data) use ($welcome_msg, $conf) {
                    ob_start();
                    try {
                        $arr = LightCacheInside::get('light_array', 'input_token') ?? [];
                        if (empty($arr[$fd] ?? '') && $conf['token'] !== '') {
                            $token = trim($data);
                            if ($token === $conf['token']) {
                                SpinLock::transaction('input_token', static function () use ($fd, $token) {
                                    $arr = LightCacheInside::get('light_array', 'input_token');
                                    $arr[$fd] = $token;
                                    LightCacheInside::set('light_array', 'input_token', $arr);
                                });
                                $serv->send($fd, capture_output([logger('trm'), 'info'], ['令牌验证成功！']));
                                $serv->send($fd, $welcome_msg . "\n>>> ");
                            } else {
                                $serv->send($fd, capture_output([logger('trm'), 'error'], ['令牌验证失败！']));
                                $serv->close($fd);
                            }
                            return;
                        }
                        if (trim($data) === 'exit' || trim($data) === 'q') {
                            $serv->send($fd, capture_output([logger('trm'), 'info'], ['再见！']));
                            $serv->close($fd);
                            return;
                        }
                        Terminal::executeCommand(trim($data));
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
                        logger('trm')->error(zm_internal_errcode('E00009') . 'Uncaught exception ' . get_class($e) . ' when calling "open": ' . $error_msg);
                        logger('trm')->error($e->getTraceAsString());
                    } catch (Error $e) {
                        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
                        logger('trm')->error(zm_internal_errcode('E00009') . 'Uncaught ' . get_class($e) . ' when calling "open": ' . $error_msg);
                        logger('trm')->error($e->getTraceAsString());
                    }

                    $r = ob_get_clean();
                    if (!empty($r)) {
                        $serv->send($fd, $r);
                    }
                    if (!in_array(trim($data), ['r', 'reload'])) {
                        $serv->send($fd, '>>> ');
                    }
                });

                $port->on('close', function ($serv, $fd) {
                    ManagerGM::popConnect($fd);
                });
            }

            // 设置服务器配置
            self::$server->set($this->swoole_server_config);
            Console::setServer(self::$server);

            // 非静默模式下，打印欢迎信息
            if (!self::$argv['private-mode']) {
                $this->printMotd();
            }

            $global_hook = ZMConfig::get('global', 'runtime')['swoole_coroutine_hook_flags'] ?? (SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL));
            if ($coroutine_mode) {
                Runtime::enableCoroutine(true, $global_hook);
            } else {
                Runtime::enableCoroutine(false, SWOOLE_HOOK_ALL);
            }

            // 注册 Swoole Server 的事件
            $this->registerServerEvents();

            // 初始化缓存
            $r = ZMConfig::get('global', 'light_cache') ?? [
                'size' => 512,                     // 最多允许储存的条数（需要2的倍数）
                'max_strlen' => 32768,               // 单行字符串最大长度（需要2的倍数）
                'hash_conflict_proportion' => 0.6,   // Hash冲突率（越大越好，但是需要的内存更多）
                'persistence_path' => DataProvider::getDataFolder() . '_cache.json',
                'auto_save_interval' => 900,
            ];
            LightCache::init($r);
            LightCacheInside::init();

            // 初始化自旋锁
            SpinLock::init($r['size']);

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
        } catch (Exception $e) {
            logger()->emergency('框架初始化失败，请检查！');
            logger()->emergency(zm_internal_errcode('E00010') . $e->getMessage());
            if (strpos($e->getMessage(), 'Address already in use') !== false) {
                if (!ProcessManager::isStateEmpty()) {
                    logger()->alert('检测到可能残留框架的工作进程，请先通过命令杀死：server:stop --force');
                }
            }
            logger()->debug($e);
            exit;
        }
    }

    public static function loadFrameworkState()
    {
        if (!file_exists(DataProvider::getDataFolder() . '.state.json')) {
            return [];
        }
        $r = json_decode(file_get_contents(DataProvider::getDataFolder() . '.state.json'), true);
        if ($r === null) {
            $r = [];
        }
        return $r;
    }

    public static function saveFrameworkState($data)
    {
        return file_put_contents(DataProvider::getDataFolder() . '.state.json', json_encode($data, 64 | 128 | 256));
    }

    public function start()
    {
        try {
            self::$loaded_files = get_included_files();
            LightCacheInside::set('tmp_kv', 'start_time', microtime(true));
            self::$server->start();
            zm_atomic('server_is_stopped')->set(1);
            if (!self::$argv['private-mode']) {
                logger()->info('炸毛框架已停止！');
            }
        } catch (Throwable $e) {
            exit(zm_internal_errcode('E00011') . '框架发生未捕获的异常：' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
        }
    }

    /**
     * 打印 MOTD
     */
    private function printMotd(): void
    {
        $tty_width = (new TablePrinter([]))->fetchTerminalSize();
        if (file_exists(DataProvider::getSourceRootDir() . '/config/motd.txt')) {
            $motd = file_get_contents(DataProvider::getSourceRootDir() . '/config/motd.txt');
        } else {
            $motd = file_get_contents(__DIR__ . '/../../config/motd.txt');
        }
        $motd = explode("\n", $motd);
        foreach ($motd as $k => $v) {
            $motd[$k] = substr($v, 0, $tty_width);
        }
        $motd = implode("\n", $motd);
        echo $motd;
    }

    /**
     * 翻译属性
     */
    private function translateProperties(array &$properties): void
    {
        $translations = [
            'working_dir' => '工作目录',
            'listen' => '监听地址',
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
            'static_file_server' => '静态文件托管',
            'php_version' => 'PHP 版本',
            'swoole_version' => 'Swoole 版本',
        ];
        foreach ($properties as $k => $v) {
            if (isset($translations[$k])) {
                $this->changeArrayKey($properties, $k, $translations[$k]);
            }
        }
    }

    private function loadServerEvents()
    {
        if (Phar::running() !== '') {
            ob_start();
            include_once DataProvider::getFrameworkRootDir() . '/src/ZM/script_setup_loader.php';
            $r = ob_get_clean();
            $result_code = 0;
        } else {
            $r = exec(PHP_BINARY . ' ' . DataProvider::getFrameworkRootDir() . '/src/ZM/script_setup_loader.php', $output, $result_code);
        }
        if ($result_code !== 0) {
            logger()->emergency('代码解析错误！');
            exit(1);
        }
        $json = json_decode($r, true);
        if (!is_array($json)) {
            logger()->error(zm_internal_errcode('E00012') . '解析 @SwooleHandler 及 @OnSetup 时发生错误，请检查代码！');
        }
        $this->setup_events = $json;
    }

    /**
     * 从全局配置文件里读取注入系统事件的类
     *
     * @throws ReflectionException
     * @throws ReflectionException
     */
    private function registerServerEvents()
    {
        $reader = new AnnotationReader();
        $all = ZMUtil::getClassesPsr4(FRAMEWORK_ROOT_DIR . '/src/ZM/Event/SwooleEvent/', 'ZM\\Event\\SwooleEvent');
        foreach ($all as $v) {
            $class = new $v();
            $reflection_class = new ReflectionClass($class);
            $anno_class = $reader->getClassAnnotation($reflection_class, SwooleHandler::class);
            if ($anno_class !== null) { // 类名形式的注解
                $this->setup_events['event'][] = [
                    'class' => $v,
                    'method' => 'onCall',
                    'event' => $anno_class->event,
                ];
            }
        }

        foreach (($this->setup_events['setup'] ?? []) as $v) {
            logger()->debug('Calling @OnSetup: ' . $v['class']);
            $c = ZMUtil::getModInstance($v['class']);
            $method = $v['method'];
            $c->{$method}();
        }

        foreach ($this->setup_events['event'] as $v) {
            self::$server->on($v['event'], function (...$param) use ($v) {
                ZMUtil::getModInstance($v['class'])->{$v['method']}(...$param);
            });
        }
    }

    /**
     * 解析命令行的 $argv 参数
     *
     * @param  array                    $args 命令行参数
     * @throws InvalidArgumentException 参数不正确，框架需要捕获并终止启动
     * @return array                    解析后并需要处理的参数
     */
    private function parseCliArgs(array $args, array $defaults): array
    {
        $coroutine_mode = true;
        global $terminal_id;
        $terminal_id = uuidgen();
        $error_occur = false;
        $remote_terminal = $defaults['remote_terminal'];

        foreach ($args as $x => $y) {
            if ($y === false || is_null($y)) {
                continue;
            }
            switch ($x) {
                case 'worker-num':
                    if ((int) $y >= 1 && (int) $y <= 1024) {
                        $this->swoole_server_config['worker_num'] = (int) $y;
                    } else {
                        logger()->emergency(zm_internal_errcode('E00013') . '传入的 worker_num 参数不合法！必须在 1-1024 之间！');
                        $error_occur = true;
                    }
                    break;
                case 'task-worker-num':
                    if ((int) $y >= 1 && (int) $y <= 1024) {
                        $this->swoole_server_config['task_worker_num'] = (int) $y;
                        $this->swoole_server_config['task_enable_coroutine'] = true;
                    } else {
                        logger()->emergency(zm_internal_errcode('E00013') . '传入的 task_worker_num 参数不合法！必须在 1-1024 之间！');
                        $error_occur = true;
                    }
                    break;
                case 'disable-coroutine':
                    $coroutine_mode = false;
                    break;
                case 'debug-mode':
                    self::$argv['disable-safe-exit'] = true;
                    $coroutine_mode = false;
                    $terminal_id = null;
                    self::$argv['watch'] = true;
                    logger()->notice('已进入调试模式，请勿在生产环境中使用！');
                    break;
                case 'daemon':
                    $this->swoole_server_config['daemonize'] = 1;
                    Console::$theme = 'no-color';
                    Console::log('已启用守护进程，输出重定向到 ' . $this->swoole_server_config['log_file']);
                    $terminal_id = null;
                    break;
                case 'disable-console-input':
                case 'no-interaction':
                    $terminal_id = null;
                    break;
                case 'log-error':
                    Console::setLevel(0);
                    break;
                case 'log-warning':
                    Console::setLevel(1);
                    break;
                case 'log-info':
                    Console::setLevel(2);
                    break;
                case 'log-verbose':
                case 'verbose':
                    Console::setLevel(3);
                    break;
                case 'log-debug':
                    Console::setLevel(4);
                    break;
                case 'audit-mode':
                    logger()->notice('审计模式已开启，请正常执行需要审计的流程，然后Ctrl+C正常结束框架');
                    logger()->notice('审计的日志文件将存放到：' . DataProvider::getWorkingDir() . '/audit.log');
                    if (file_exists(DataProvider::getWorkingDir() . '/audit.log')) {
                        unlink(DataProvider::getWorkingDir() . '/audit.log');
                    }
                    logger()->notice('框架将于5秒后开始启动...');
                    Console::setOutputFile(DataProvider::getWorkingDir() . '/audit.log');
                    Console::setLevel(4);
                    sleep(5);
                    break;
                case 'log-theme':
                    Console::$theme = $y;
                    break;
                case 'remote-terminal':
                    $remote_terminal = true;
                    break;
                case 'show-php-ver':
                default:
                    break;
            }
        }
        if ($error_occur) {
            throw new InvalidArgumentException('命令行参数解析错误，请参阅上方日志！');
        }
        return [$coroutine_mode, $terminal_id, $remote_terminal];
    }

    /**
     * 更换数组键名
     *
     * @param mixed $old_key
     * @param mixed $new_key
     */
    private function changeArrayKey(array &$arr, $old_key, $new_key): void
    {
        $keys = array_keys($arr);
        $keys[array_search($old_key, $keys, false)] = $new_key;
        $arr = ($t = array_combine($keys, $arr)) ? $t : $arr;
    }

    /**
     * 打印属性表格
     */
    private function printProperties(bool $remote_terminal, array $args): void
    {
        $properties = [];
        $properties['working_dir'] = DataProvider::getWorkingDir();
        $properties['listen'] = ZMConfig::get('global', 'host') . ':' . ZMConfig::get('global', 'port');
        if (!isset($this->swoole_server_config['worker_num'])) {
            if ((ZMConfig::get('global', 'runtime')['swoole_server_mode'] ?? SWOOLE_PROCESS) === SWOOLE_PROCESS) {
                $properties['worker'] = swoole_cpu_num() . ' (auto)';
            } else {
                $properties['single_proc_mode'] = 'true';
            }
        } else {
            $properties['worker'] = $this->swoole_server_config['worker_num'];
        }
        $properties['environment'] = ($args['env'] ?? null) === null ? 'default' : $args['env'];
        $properties['log_level'] = strtoupper(zm_config('logging.level'));
        $properties['version'] = self::VERSION . (LOAD_MODE === 0 ? (' (build ' . ZM_VERSION_ID . ')') : '');
        $properties['master_pid'] = posix_getpid();
        if (APP_VERSION !== 'unknown') {
            $properties['app_version'] = APP_VERSION;
        }
        if (isset($this->swoole_server_config['task_worker_num'])) {
            $properties['task_worker'] = $this->swoole_server_config['task_worker_num'];
        }
        if ((ZMConfig::get('global', 'sql_config')['sql_host'] ?? '') !== '') {
            $conf = ZMConfig::get('global', 'sql_config');
            $properties['mysql_pool'] = $conf['sql_database'] . '@' . $conf['sql_host'] . ':' . $conf['sql_port'];
        }
        if ((ZMConfig::get('global', 'mysql_config')['host'] ?? '') !== '') {
            $conf = ZMConfig::get('global', 'mysql_config');
            $properties['mysql'] = $conf['dbname'] . '@' . $conf['host'] . ':' . $conf['port'];
        }
        if (ZMConfig::get('global', 'redis_config')['host'] !== '') {
            $conf = ZMConfig::get('global', 'redis_config');
            $properties['redis_pool'] = $conf['host'] . ':' . $conf['port'];
        }
        if (ZMConfig::get('global', 'static_file_server')['status'] !== false) {
            $properties['static_file_server'] = 'enabled';
        }
        if (self::$argv['show-php-ver'] !== false) {
            $properties['php_version'] = PHP_VERSION;
            $properties['swoole_version'] = SWOOLE_VERSION;
        }

        if ($remote_terminal) {
            $conf = ZMConfig::get('global', 'remote_terminal');
            $properties['terminal'] = $conf['host'] . ':' . $conf['port'];
        }
        if (LOAD_MODE === 0) {
            logger()->info('框架正以源码模式启动');
        }
        $this->translateProperties($properties);
        $printer = new TablePrinter($properties);
        $printer->setValueColor('random')->printAll();
    }
}
