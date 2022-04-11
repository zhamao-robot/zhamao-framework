<?php

declare(strict_types=1);

namespace ZM;

use Doctrine\Common\Annotations\AnnotationReader;
use Error;
use Exception;
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
use ZM\Console\TermColor;
use ZM\Exception\ZMKnownException;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\DataProvider;
use ZM\Utils\Terminal;
use ZM\Utils\ZMUtil;

class Framework
{
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
     * @param array $args         运行参数
     * @param bool  $instant_mode 是否为单文件模式
     */
    public function __construct(array $args = [], bool $instant_mode = false)
    {
        $tty_width = $this->getTtyWidth();
        self::$instant_mode = $instant_mode;
        self::$argv = $args;

        // 初始化配置
        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args['env'] ?? '');
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
            echo zm_internal_errcode('E00008') . $e->getMessage() . PHP_EOL;
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
            $add_port = ZMConfig::get('global', 'remote_terminal')['status'] ?? false;

            // 加载服务器事件
            if (!$instant_mode) {
                $this->loadServerEvents();
            }

            // 解析命令行参数
            $this->parseCliArgs(self::$argv, $add_port);

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
                $out['working_dir'] = DataProvider::getWorkingDir();
                $out['listen'] = ZMConfig::get('global', 'host') . ':' . ZMConfig::get('global', 'port');
                if (!isset($this->swoole_server_config['worker_num'])) {
                    if ((ZMConfig::get('global', 'runtime')['swoole_server_mode'] ?? SWOOLE_PROCESS) === SWOOLE_PROCESS) {
                        $out['worker'] = swoole_cpu_num() . ' (auto)';
                    } else {
                        $out['single_proc_mode'] = 'true';
                    }
                } else {
                    $out['worker'] = $this->swoole_server_config['worker_num'];
                }
                $out['environment'] = ($args['env'] ?? null) === null ? 'default' : $args['env'];
                $out['log_level'] = Console::getLevel();
                $out['version'] = ZM_VERSION . (LOAD_MODE === 0 ? (' (build ' . ZM_VERSION_ID . ')') : '');
                $out['master_pid'] = posix_getpid();
                if (APP_VERSION !== 'unknown') {
                    $out['app_version'] = APP_VERSION;
                }
                if (isset($this->swoole_server_config['task_worker_num'])) {
                    $out['task_worker'] = $this->swoole_server_config['task_worker_num'];
                }
                if ((ZMConfig::get('global', 'sql_config')['sql_host'] ?? '') !== '') {
                    $conf = ZMConfig::get('global', 'sql_config');
                    $out['mysql_pool'] = $conf['sql_database'] . '@' . $conf['sql_host'] . ':' . $conf['sql_port'];
                }
                if ((ZMConfig::get('global', 'mysql_config')['host'] ?? '') !== '') {
                    $conf = ZMConfig::get('global', 'mysql_config');
                    $out['mysql'] = $conf['dbname'] . '@' . $conf['host'] . ':' . $conf['port'];
                }
                if (ZMConfig::get('global', 'redis_config')['host'] !== '') {
                    $conf = ZMConfig::get('global', 'redis_config');
                    $out['redis_pool'] = $conf['host'] . ':' . $conf['port'];
                }
                if (ZMConfig::get('global', 'static_file_server')['status'] !== false) {
                    $out['static_file_server'] = 'enabled';
                }
                if (self::$argv['show-php-ver'] !== false) {
                    $out['php_version'] = PHP_VERSION;
                    $out['swoole_version'] = SWOOLE_VERSION;
                }

                if ($add_port) {
                    $conf = ZMConfig::get('global', 'remote_terminal');
                    $out['terminal'] = $conf['host'] . ':' . $conf['port'];
                }
                self::printProps($out, $tty_width, $args['log-theme'] === null);
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
            if ($add_port) {
                $conf = ZMConfig::get('global', 'remote_terminal') ?? [
                    'status' => true,
                    'host' => '127.0.0.1',
                    'port' => 20002,
                    'token' => '',
                ];
                $welcome_msg = Console::setColor('Welcome! You can use `help` for usage.', 'green');
                /** @var Port $port */
                $port = self::$server->listen($conf['host'], $conf['port'], SWOOLE_SOCK_TCP);
                $port->set([
                    'open_http_protocol' => false,
                ]);
                $port->on('connect', function (\Swoole\Server $serv, $fd) use ($welcome_msg, $conf) {
                    ManagerGM::pushConnect($fd, 'terminal');
                    // 推送欢迎信息
                    $serv->send($fd, file_get_contents(working_dir() . '/config/motd.txt'));
                    // 要求输入令牌
                    if (!empty($conf['token'])) {
                        $serv->send($fd, 'Please input token: ');
                    } else {
                        $serv->send($fd, $welcome_msg . "\n>>> ");
                    }
                });

                $port->on('receive', function ($serv, $fd, $reactor_id, $data) use ($welcome_msg, $conf) {
                    ob_start();
                    try {
                        $arr = LightCacheInside::get('light_array', 'input_token') ?? [];
                        if (empty($arr[$fd] ?? '')) {
                            if ($conf['token'] != '') {
                                $token = trim($data);
                                if ($token === $conf['token']) {
                                    SpinLock::transaction('input_token', function () use ($fd, $token) {
                                        $arr = LightCacheInside::get('light_array', 'input_token');
                                        $arr[$fd] = $token;
                                        LightCacheInside::set('light_array', 'input_token', $arr);
                                    });
                                    $serv->send($fd, Console::setColor("Auth success!!\n", 'green'));
                                    $serv->send($fd, $welcome_msg . "\n>>> ");
                                } else {
                                    $serv->send($fd, Console::setColor("Auth failed!!\n", 'red'));
                                    $serv->close($fd);
                                }
                                return;
                            }
                        }
                        if (trim($data) == 'exit' || trim($data) == 'q') {
                            $serv->send($fd, Console::setColor("Bye!\n", 'blue'));
                            $serv->close($fd);
                            return;
                        }
                        Terminal::executeCommand(trim($data));
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
                        Console::error(zm_internal_errcode('E00009') . 'Uncaught exception ' . get_class($e) . ' when calling "open": ' . $error_msg);
                        Console::trace();
                    } catch (Error $e) {
                        $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
                        Console::error(zm_internal_errcode('E00009') . 'Uncaught ' . get_class($e) . ' when calling "open": ' . $error_msg);
                        Console::trace();
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
                    // echo "Client: Close.\n";
                });
            }

            // 设置服务器配置
            self::$server->set($this->swoole_server_config);
            Console::setServer(self::$server);

            // 非静默模式下，打印欢迎信息
            if (!self::$argv['private-mode']) {
                self::printMotd($tty_width);
            }

            global $asd;
            $asd = get_included_files();

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
                switch ($error_no) {
                    case E_WARNING:
                        $level_tips = 'PHP Warning: ';
                        break;
                    case E_NOTICE:
                        $level_tips = 'PHP Notice: ';
                        break;
                    case E_DEPRECATED:
                        $level_tips = 'PHP Deprecated: ';
                        break;
                    case E_USER_ERROR:
                        $level_tips = 'User Error: ';
                        break;
                    case E_USER_WARNING:
                        $level_tips = 'User Warning: ';
                        break;
                    case E_USER_NOTICE:
                        $level_tips = 'User Notice: ';
                        break;
                    case E_USER_DEPRECATED:
                        $level_tips = 'User Deprecated: ';
                        break;
                    case E_STRICT:
                        $level_tips = 'PHP Strict: ';
                        break;
                    default:
                        $level_tips = 'Unkonw Type Error: ';
                        break;
                }
                $error = $level_tips . $error_msg . ' in ' . $error_file . ' on ' . $error_line;
                Console::warning($error);
                // 如果 return false 则错误会继续递交给 PHP 标准错误处理
                return true;
            }, E_ALL | E_STRICT);
        } catch (Exception $e) {
            Console::error('框架初始化出现异常，请检查！');
            Console::error(zm_internal_errcode('E00010') . $e->getMessage());
            Console::debug($e);
            exit;
        }
    }

    /**
     * 将各进程的pid写入文件，以备后续崩溃及僵尸进程处理使用
     *
     * @param int|string $pid
     * @internal
     */
    public static function saveProcessState(int $type, $pid, array $data = [])
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = _zm_pid_dir() . '/master.json';
                $json = [
                    'pid' => intval($pid),
                    'stdout' => $data['stdout'],
                    'daemon' => $data['daemon'],
                ];
                file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE));
                return;
            case ZM_PROCESS_MANAGER:
                $file = _zm_pid_dir() . '/manager.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_WORKER:
                $file = _zm_pid_dir() . '/worker.' . $data['worker_id'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_USER:
                $file = _zm_pid_dir() . '/user.' . $data['process_name'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_TASKWORKER:
                $file = _zm_pid_dir() . '/taskworker.' . $data['worker_id'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
        }
    }

    /**
     * 用于框架内部获取多进程运行状态的函数
     *
     * @param  mixed            $id_or_name
     * @throws ZMKnownException
     * @return false|int|mixed
     * @internal
     */
    public static function getProcessState(int $type, $id_or_name = null)
    {
        $file = _zm_pid_dir();
        switch ($type) {
            case ZM_PROCESS_MASTER:
                if (!file_exists($file . '/master.json')) {
                    return false;
                }
                $json = json_decode(file_get_contents($file . '/master.json'), true);
                if ($json !== null) {
                    return $json;
                }
                return false;
            case ZM_PROCESS_MANAGER:
                if (!file_exists($file . '/manager.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/manager.pid'));
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists($file . '/worker.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/worker.' . $id_or_name . '.pid'));
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                if (!file_exists($file . '/user.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/user.' . $id_or_name . '.pid'));
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists($file . '/taskworker.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/taskworker.' . $id_or_name . '.pid'));
            default:
                return false;
        }
    }

    /**
     * @param  null|int|string  $id_or_name
     * @throws ZMKnownException
     * @internal
     */
    public static function removeProcessState(int $type, $id_or_name = null)
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = _zm_pid_dir() . '/master.json';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_MANAGER:
                $file = _zm_pid_dir() . '/manager.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = _zm_pid_dir() . '/worker.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                $file = _zm_pid_dir() . '/user.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = _zm_pid_dir() . '/taskworker.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
        }
    }

    public function start()
    {
        try {
            self::$loaded_files = get_included_files();
            self::$server->start();
            zm_atomic('server_is_stopped')->set(1);
            Console::log('zhamao-framework is stopped.');
        } catch (Throwable $e) {
            exit(zm_internal_errcode('E00011') . 'Framework has an uncaught ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
        }
    }

    public static function printProps($out, $tty_width, $colorful = true)
    {
        $max_border = min($tty_width, 65);
        if (LOAD_MODE === 0) {
            echo Console::setColor("* Framework started with source mode.\n", $colorful ? 'yellow' : '');
        }
        echo str_pad('', $max_border, '=') . PHP_EOL;

        $current_line = 0;
        $line_width = [];
        $line_data = [];
        foreach ($out as $k => $v) {
            start:
            if (!isset($line_width[$current_line])) {
                $line_width[$current_line] = $max_border - 2;
            }
            // Console::info("行宽[$current_line]：".$line_width[$current_line]);
            if ($max_border >= 57) { // 很宽的时候，一行能放两个短行
                if ($line_width[$current_line] === ($max_border - 2)) { // 空行
                    self::writeNoDouble($k, $v, $line_data, $line_width, $current_line, $colorful, $max_border);
                } else { // 不是空行，已经有东西了
                    $tmp_line = $k . ': ' . $v;
                    // Console::info("[$current_line]即将插入后面的东西[".$tmp_line."]");
                    if (strlen($tmp_line) > $line_width[$current_line]) { // 地方不够，另起一行
                        $line_data[$current_line] = str_replace('|  ', '', $line_data[$current_line]);
                        ++$current_line;
                        goto start;
                    }   // 地方够，直接写到后面并另起一行
                    $line_data[$current_line] .= $k . ': ';
                    if ($colorful) {
                        $line_data[$current_line] .= TermColor::color8(32);
                    }
                    $line_data[$current_line] .= $v;
                    if ($colorful) {
                        $line_data[$current_line] .= TermColor::RESET;
                    }
                    ++$current_line;
                }
            } else {  // 不够宽，直接写单行
                self::writeNoDouble($k, $v, $line_data, $line_width, $current_line, $colorful, $max_border);
            }
        }
        foreach ($line_data as $v) {
            echo $v . PHP_EOL;
        }
        echo str_pad('', $max_border, '=') . PHP_EOL;
    }

    public function getTtyWidth(): int
    {
        $size = exec('stty size');
        if (empty($size)) {
            return 65;
        }
        return (int) explode(' ', trim($size))[1];
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

    private static function printMotd($tty_width)
    {
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
     * @noinspection PhpIncludeInspection
     */
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
            Console::error('Parsing code error!');
            exit(1);
        }
        $json = json_decode($r, true);
        if (!is_array($json)) {
            Console::warning(zm_internal_errcode('E00012') . 'Parsing @SwooleHandler and @OnSetup error!');
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
            Console::debug('Calling @OnSetup: ' . $v['class']);
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
     * 解析命令行的 $argv 参数们
     *
     * @param array       $args     命令行参数
     * @param bool|string $add_port 是否添加端口号
     */
    private function parseCliArgs(array $args, &$add_port)
    {
        $coroutine_mode = true;
        global $terminal_id;
        $terminal_id = uuidgen();
        foreach ($args as $x => $y) {
            if ($y) {
                switch ($x) {
                    case 'worker-num':
                        if (intval($y) >= 1 && intval($y) <= 1024) {
                            $this->swoole_server_config['worker_num'] = intval($y);
                        } else {
                            Console::warning(zm_internal_errcode('E00013') . 'Invalid worker num! Turn to default value (' . ($this->swoole_server_config['worker_num'] ?? swoole_cpu_num()) . ')');
                        }
                        break;
                    case 'task-worker-num':
                        if (intval($y) >= 1 && intval($y) <= 1024) {
                            $this->swoole_server_config['task_worker_num'] = intval($y);
                            $this->swoole_server_config['task_enable_coroutine'] = true;
                        } else {
                            Console::warning(zm_internal_errcode('E00013') . 'Invalid worker num! Turn to default value (0)');
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
                        echo "* You are in debug mode, do not use in production!\n";
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
                    case 'log-theme':
                        Console::$theme = $y;
                        break;
                    case 'remote-terminal':
                        $add_port = true;
                        break;
                    case 'show-php-ver':
                    default:
                        // Console::info("Calculating ".$x);
                        // dump($y);
                        break;
                }
            }
        }
        $global_hook = ZMConfig::get('global', 'runtime')['swoole_coroutine_hook_flags'] ?? (SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL));
        if ($coroutine_mode) {
            Runtime::enableCoroutine(true, $global_hook);
        } else {
            Runtime::enableCoroutine(false, SWOOLE_HOOK_ALL);
        }
    }

    private static function writeNoDouble($k, $v, &$line_data, &$line_width, &$current_line, $colorful, $max_border)
    {
        $tmp_line = $k . ': ' . $v;
        // Console::info("写入[".$tmp_line."]");
        if (strlen($tmp_line) > $line_width[$current_line]) { // 输出的内容太多了，以至于一行都放不下一个，要折行
            $title_strlen = strlen($k . ': ');
            $content_len = $line_width[$current_line] - $title_strlen;

            $line_data[$current_line] = ' ' . $k . ': ';
            if ($colorful) {
                $line_data[$current_line] .= TermColor::color8(32);
            }
            $line_data[$current_line] .= substr($v, 0, $content_len);
            if ($colorful) {
                $line_data[$current_line] .= TermColor::RESET;
            }
            $rest = substr($v, $content_len);
            ++$current_line; // 带标题的第一行满了，折到第二行
            do {
                if ($colorful) {
                    $line_data[$current_line] = TermColor::color8(32);
                }
                $line_data[$current_line] .= ' ' . substr($rest, 0, $max_border - 2);
                if ($colorful) {
                    $line_data[$current_line] .= TermColor::RESET;
                }
                $rest = substr($rest, $max_border - 2);
                ++$current_line;
            } while ($rest > $max_border - 2); // 循环，直到放完
        } else { // 不需要折行
            // Console::info("不需要折行");
            $line_data[$current_line] = ' ' . $k . ': ';
            if ($colorful) {
                $line_data[$current_line] .= TermColor::color8(32);
            }
            $line_data[$current_line] .= $v;
            if ($colorful) {
                $line_data[$current_line] .= TermColor::RESET;
            }

            if ($max_border >= 57) {
                if (strlen($tmp_line) >= intval(($max_border - 2) / 2)) {  // 不需要折行，直接输出一个转下一行
                    // Console::info("不需要折行，直接输出一个转下一行");
                    ++$current_line;
                } else {  // 输出很小，写到前面并分片
                    // Console::info("输出很小，写到前面并分片");
                    $space = intval($max_border / 2) - 2 - strlen($tmp_line);
                    $line_data[$current_line] .= str_pad('', $space);
                    $line_data[$current_line] .= '|  '; // 添加分片
                    $line_width[$current_line] -= (strlen($tmp_line) + 3 + $space);
                }
            } else {
                ++$current_line;
            }
        }
    }
}
