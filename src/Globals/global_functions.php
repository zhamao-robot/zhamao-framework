<?php

declare(strict_types=1);

use Choir\Http\Client\Exception\ClientException;
use Choir\Http\HttpFactory;
use OneBot\Driver\Coroutine\Adaptive;
use OneBot\Driver\Coroutine\CoroutineInterface;
use OneBot\Driver\Interfaces\WebSocketClientInterface;
use OneBot\Driver\Process\ExecutionResult;
use OneBot\Driver\Socket\WSServerSocketBase;
use OneBot\Driver\Workerman\WebSocketClient;
use OneBot\V12\Object\MessageSegment;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ZM\Config\Environment;
use ZM\Config\ZMConfig;
use ZM\Container\ContainerHolder;
use ZM\Exception\DriverException;
use ZM\Framework;
use ZM\Logger\ConsoleLogger;
use ZM\Middleware\MiddlewareHandler;
use ZM\Plugin\OneBot\BotMap;
use ZM\Plugin\ZMPlugin;
use ZM\Schedule\Timer;
use ZM\Store\Database\DBException;
use ZM\Store\Database\DBPool;
use ZM\Store\Database\DBQueryBuilder;
use ZM\Store\Database\DBWrapper;
use ZM\Store\KV\KVInterface;
use ZM\Store\KV\Redis\RedisWrapper;
use ZM\ZMApplication;

// 防止重复引用引发报错
if (function_exists('zm_internal_errcode')) {
    return;
}

/**
 * 根据具体操作系统替换目录分隔符
 *
 * @param string $dir 目录
 */
function zm_dir(string $dir): string
{
    if (str_starts_with($dir, 'phar://')) {
        return $dir;
    }
    return str_replace('/', DIRECTORY_SEPARATOR, $dir);
}

/**
 * 执行shell指令
 *
 * @param string $cmd 命令行
 */
function zm_exec(string $cmd): ExecutionResult
{
    return Adaptive::exec($cmd);
}

/**
 * sleep 指定时间，单位为秒（最小单位为1毫秒，即0.001）
 */
function zm_sleep(float|int $time)
{
    Adaptive::sleep($time);
}

/**
 * 创建一个计时器（Timer::tick() 的别名）
 *
 * @param int      $ms       时间（毫秒）
 * @param callable $callback 回调函数
 * @param int      $times    重复次数（如果为 0 或 -1，则永久循环，其他大于 0 的数为限定次数）
 */
function zm_timer_tick(int $ms, callable $callback, int $times = 0): int
{
    return Timer::tick($ms, $callback, $times);
}

/**
 * 创建一个延后一次性计时器，只在指定毫秒后执行一次即销毁（Timer::after() 的别名）
 *
 * @param int      $ms       时间（毫秒）
 * @param callable $callback 回调函数
 */
function zm_timer_after(int $ms, callable $callback): int
{
    return Timer::after($ms, $callback);
}

/**
 * 获取协程接口
 */
function coroutine(): ?CoroutineInterface
{
    return Adaptive::getCoroutine();
}

/**
 * 获取内部错误码
 */
function zm_internal_errcode(int|string $code): string
{
    return "[ErrCode:{$code}] ";
}

/**
 * 返回当前炸毛实例的 ID
 */
function zm_instance_id(): string
{
    if (defined('ZM_INSTANCE_ID')) {
        return ZM_INSTANCE_ID;
    }
    if (!defined('ZM_START_TIME')) {
        define('ZM_START_TIME', microtime(true));
    }
    $instance_id = dechex(crc32(strval(ZM_START_TIME)));
    define('ZM_INSTANCE_ID', $instance_id);
    return ZM_INSTANCE_ID;
}

/**
 * 助手方法，返回一个 Logger 实例
 */
function logger(): LoggerInterface
{
    global $ob_logger;
    if ($ob_logger === null) {
        return new ConsoleLogger();
    }
    return $ob_logger;
}

/**
 * 判断传入的数组是否为关联数组
 */
function is_assoc_array(array $array): bool
{
    return !empty($array) && array_keys($array) !== range(0, count($array) - 1);
}

/**
 * 格式匹配
 */
function match_pattern(string $pattern, string $subject): bool
{
    $pattern = str_replace(['\*', '\\\\.*'], ['.*', '\*'], preg_quote($pattern, '/'));
    $pattern = '/^' . $pattern . '$/i';
    return preg_match($pattern, $subject) === 1;
}

/**
 * 匹配参数
 *
 * @return array|false 成功时返回匹配到的参数数组，失败时返回false
 */
function match_args(string $pattern, string $subject)
{
    $result = [];
    if (match_pattern($pattern, $subject)) {
        if (mb_strpos($pattern, '*') === false) {
            return [];
        }
        $exp = explode('*', $pattern);
        $i = 0;
        foreach ($exp as $k => $v) {
            if (empty($v) && $k === 0) {
                continue;
            }
            if (empty($v) && $k === count($exp) - 1) {
                $subject .= '^EOL';
                $v = '^EOL';
            }
            $cur_var = '';
            $ori = $i;
            while (($a = mb_substr($subject, $i, mb_strlen($v))) !== $v && !empty($a)) {
                $cur_var .= mb_substr($subject, $i, 1);
                ++$i;
            }
            if ($i !== $ori || $k === 1 || $k === count($exp) - 1) {
                $result[] = $cur_var;
            }
            $i += mb_strlen($v);
        }
        return $result;
    }
    return false;
}

/**
 * 构建消息段的助手函数
 *
 * @param string $type 类型
 * @param array  $data 字段
 */
function segment(string $type, array $data = []): MessageSegment
{
    return new MessageSegment($type, $data);
}

/**
 * 中间件操作类的助手函数
 */
function middleware(): MiddlewareHandler
{
    return MiddlewareHandler::getInstance();
}

// ////////////////// 容器部分 //////////////////////

/**
 * 获取容器实例
 */
function container(): DI\Container
{
    return ContainerHolder::getEventContainer();
}

/**
 * 解析类实例（使用容器）
 *
 * 这是 {@see container()}->make($abstract, $parameters) 的别名
 *
 * @template     T
 * @param  class-string<T> $abstract
 * @return Closure|mixed|T
 */
function resolve(string $abstract, array $parameters = [])
{
    /* @noinspection PhpUnhandledExceptionInspection */
    return container()->make($abstract, $parameters);
}

/**
 * 获取 MySQL 调用的类
 *
 * @throws DBException
 */
function db(string $name = '')
{
    return new DBWrapper($name);
}

/**
 * 获取构建 MySQL 的类
 *
 * @throws DBException
 */
function sql_builder(string $name = ''): DBQueryBuilder
{
    return (new DBWrapper($name))->createQueryBuilder();
}

/**
 * 获取一个便携 SQLite 操作类
 *
 * @param  string      $name       使用的 SQLite 连接文件名
 * @param  bool        $create_new 是否在文件不存在时创建新的
 * @param  bool        $keep_alive 是否维持 PDO 对象以便优化性能
 * @throws DBException
 */
function zm_sqlite(string $name, bool $create_new = true, bool $keep_alive = true): DBWrapper
{
    return DBPool::createPortableSqlite($name, $create_new, $keep_alive);
}

/**
 * 获取便携 SQLite 操作类的 SQL 语句构造器
 *
 * @param  string      $name       使用的 SQLite 连接文件名
 * @param  bool        $create_new 是否在文件不存在时创建新的
 * @param  bool        $keep_alive 是否维持 PDO 对象以便优化性能
 * @throws DBException
 */
function zm_sqlite_builder(string $name, bool $create_new = true, bool $keep_alive = true): DBQueryBuilder
{
    return zm_sqlite($name, $create_new, $keep_alive)->createQueryBuilder();
}

/**
 * 获取 Redis 操作类
 *
 * @param string $name 使用的 Redis 连接名称
 */
function redis(string $name = 'default'): RedisWrapper
{
    return new RedisWrapper($name);
}

/**
 * 获取 / 设置配置项
 *
 * 传入键名和（或）默认值，获取配置项
 * 传入数组，设置配置项
 * 不传参数，返回配置容器
 *
 * @param  null|array|string   $key     键名
 * @param  null|mixed          $default 默认值
 * @return mixed|void|ZMConfig
 */
function config(array|string $key = null, mixed $default = null)
{
    $config = ZMConfig::getInstance();
    if (is_null($key)) {
        return $config;
    }
    if (is_array($key)) {
        $config->set($key);
        return;
    }
    return $config->get($key, $default);
}

function bot(string $bot_id = '', string $platform = ''): ZM\Context\BotContext
{
    return BotMap::getBotContext($bot_id, $platform);
}

function bot_connect(int $flag, int $fd)
{
    return BotMap::getConnectContext($flag, $fd);
}

/**
 * 获取一个 KV 库实例
 *
 * @param  string         $name KV 库名称
 * @return CacheInterface
 */
function kv(string $name = ''): Psr\SimpleCache\CacheInterface
{
    global $kv_class;
    if (!$kv_class) {
        $kv_class = config('global.kv.use', LightCache::class);
    }
    /* @phpstan-ignore-next-line */
    return is_a($kv_class, KVInterface::class, true) ? $kv_class::open($name) : new $kv_class($name);
}

/**
 * 获取环境变量
 */
function env(string $key, mixed $default = null): mixed
{
    // TODO: 重新思考容器绑定的加载方式，从而在此处使用 interface
    return resolve(Environment::class)->get($key, $default);
}

/**
 * 【助手函数】HttpFactory 快速创建一个 Response
 *
 * @param int         $status_code 状态码
 * @param null|string $reason      原因（留空则使用状态码本身的）
 * @param array       $headers     请求头
 * @param mixed       $body        HTTP Body
 * @param string      $protocol    HTTP 协议版本
 */
function zm_http_response(int $status_code = 200, ?string $reason = null, array $headers = [], mixed $body = null, string $protocol = '1.1'): ResponseInterface
{
    return HttpFactory::createResponse($status_code, $reason, $headers, $body, $protocol);
}

/**
 * 【助手函数】获取驱动的 WebSocket 服务器对应 Socket 操作对象
 *
 * @param  int       $flag 对应的 Server 端口标记
 * @throws Exception
 */
function ws_socket(int $flag = 1): WSServerSocketBase
{
    $a = Framework::getInstance()->getDriver()->getWSServerSocketByFlag($flag);
    if ($a === null) {
        throw new Exception('找不到目标的 server socket，请检查 flag 值');
    }
    return $a;
}

/**
 * 创建炸毛框架应用
 */
function zm_create_app(): ZMApplication
{
    return new ZMApplication();
}

/**
 * 创建炸毛框架的插件对象
 */
function zm_create_plugin(): ZMPlugin
{
    return new ZMPlugin();
}

/**
 * 创建一个 WebSocket 客户端
 *
 * @param  string          $address 接入地址，例如 ws://192.168.1.3:9998/
 * @param  array           $header  请求头
 * @param  null|mixed      $set     Swoole 驱动下传入的额外参数
 * @throws DriverException
 * @throws ClientException
 */
function zm_websocket_client(string $address, array $header = [], mixed $set = null): WebSocketClientInterface
{
    return match (Framework::getInstance()->getDriver()->getName()) {
        'swoole' => \OneBot\Driver\Swoole\WebSocketClient::createFromAddress($address, $header, $set ?? ['websocket_mask' => true]),
        'workerman' => WebSocketClient::createFromAddress($address, $header),
        default => throw new DriverException('current driver is not supported for creating websocket client'),
    };
}
