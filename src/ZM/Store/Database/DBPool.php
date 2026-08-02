<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\Database;

use OneBot\Driver\Driver;
use OneBot\Driver\Interfaces\PoolInterface;
use OneBot\Driver\Swoole\ObjectPool as SwooleObjectPool;
use OneBot\Driver\Swoole\SwooleDriver;
use OneBot\Driver\Workerman\ObjectPool as WorkermanObjectPool;
use OneBot\Driver\Workerman\WorkermanDriver;
use ZM\Store\FileSystem;

class DBPool
{
    /**
     * 每个便携 SQLite 数据库文件的连接池容量（同一文件最多同时持有的复用连接数）
     *
     * 可通过配置 global.database.portable_pool_size 覆盖
     */
    private const PORTABLE_POOL_SIZE = 4;

    /**
     * 便携 SQLite 连接池的常驻数量上限（超出后按 LRU 淘汰最久未访问的池）
     *
     * 可通过配置 global.database.portable_pool_max 覆盖
     */
    private const PORTABLE_POOL_MAX = 64;

    /**
     * @var array<string, SwooleObjectPool|WorkermanObjectPool> 连接池列表
     */
    private static array $pools = [];

    /**
     * @var array<string, PoolInterface> 便携 SQLite 连接池注册表（键为解析后的数据库文件路径）
     */
    private static array $portable_pools = [];

    /**
     * 重新初始化连接池，有时候连不上某个对象时候可以使用，也可以定期调用释放链接
     *
     * @throws DBException
     */
    public static function resetPools(): void
    {
        // 清空 MySQL 的连接池
        foreach (DBPool::getAllPools() as $name => $pool) {
            DBPool::destroyPool($name);
        }

        // 读取 MySQL/PostgresSQL/SQLite 配置文件并创建连接池
        $conf = config('global.database');
        // 如果有多个数据库连接，则遍历
        foreach ($conf as $name => $conn_conf) {
            if (($conn_conf['enable'] ?? true) !== false) {
                DBPool::create($name, $conn_conf);
            }
        }
    }

    /**
     * 重新初始化所有的便携 SQLite 连接池（其实就是断开所有连接）
     */
    public static function resetPortableSQLite(): void
    {
        self::$portable_pools = [];
    }

    /**
     * 通过配置文件创建一个 MySQL 连接池
     *
     * @throws DBException
     */
    public static function create(string $name, array $config)
    {
        $size = $config['pool_size'] ?? 64;
        switch ($config['type']) {
            case 'mysql':
                $connect_str = 'mysql:host={host};port={port};dbname={dbname};charset={charset}';
                $table = [
                    '{host}' => $config['host'],
                    '{port}' => $config['port'],
                    '{dbname}' => $config['dbname'],
                    '{charset}' => $config['charset'] ?? 'utf8mb4',
                ];
                $connect_str = str_replace(array_keys($table), array_values($table), $connect_str);
                $args = [$config['username'], $config['password'], $config['options'] ?? []];
                self::checkMysqlExtension();
                break;
            case 'sqlite':
                $connect_str = 'sqlite:{dbname}';
                if (FileSystem::isRelativePath($config['dbname'])) {
                    $config['dbname'] = zm_dir(SOURCE_ROOT_DIR . '/' . $config['dbname']);
                }
                $table = [
                    '{dbname}' => $config['dbname'],
                ];
                $args = [];
                $connect_str = str_replace(array_keys($table), array_values($table), $connect_str);
                break;
            case 'postgres':
            case 'pgsql':
                $connect_str = 'pgsql:host={host};port={port};dbname={dbname};user={username};password={password}';
                $table = [
                    '{host}' => $config['host'],
                    '{port}' => $config['port'],
                    '{dbname}' => $config['dbname'],
                    '{username}' => $config['username'],
                    '{password}' => $config['password'],
                ];
                $connect_str = str_replace(array_keys($table), array_values($table), $connect_str);
                $args = [];
                break;
            default:
                throw new DBException('type ' . $config['type'] . ' not supported yet');
        }
        switch (Driver::getActiveDriverClass()) {
            case WorkermanDriver::class:
                self::$pools[$name] = new WorkermanObjectPool($size, \PDO::class, $connect_str, ...$args);
                break;
            case SwooleDriver::class:
                self::$pools[$name] = new SwooleObjectPool($size, \PDO::class, $connect_str, ...$args);
        }
        switch ($config['type']) {
            case 'sqlite':
                /** @var \PDO $pool */
                $pool = self::$pools[$name]->get();
                $a = $pool->query('select sqlite_version();')->fetchAll()[0][0] ?? '';
                if (str_starts_with($a, '3')) {
                    logger()->debug('sqlite ' . $name . ' connected');
                }
                self::$pools[$name]->put($pool);
                break;
            case 'mysql':
                // TODO: 编写验证 MySQL 连接有效性的功能
                break;
            case 'postgres':
            case 'pgsql':
                $pool = self::$pools[$name]->get();
                $a = $pool->query('select version();')->fetchAll()[0][0] ?? '';
                if (str_starts_with($a, 'PostgreSQL')) {
                    logger()->debug('pgsql ' . $name . ' connected');
                }
                self::$pools[$name]->put($pool);
                break;
        }
    }

    /**
     * 获取一个数据库连接池
     *
     * @param string $name 连接池名称
     */
    public static function pool(string $name): PoolInterface
    {
        if (!isset(self::$pools[$name]) && count(self::$pools) !== 1) {
            throw new \RuntimeException("Pool {$name} not found");
        }
        return self::$pools[$name] ?? self::$pools[array_key_first(self::$pools)];
    }

    /**
     * 获取所有数据库连接池
     *
     * @return PoolInterface[]
     */
    public static function getAllPools(): array
    {
        return self::$pools;
    }

    /**
     * 销毁数据库连接池
     *
     * @param string $name 数据库连接池名称
     */
    public static function destroyPool(string $name)
    {
        unset(self::$pools[$name]);
    }

    /**
     * 检查数据库启动必要的依赖扩展，如果不符合要求则抛出异常
     *
     * @throws DBException
     */
    public static function checkMysqlExtension()
    {
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
                throw new DBException(zm_internal_errcode('E00028') . '未安装 mysqlnd php-mysql扩展。');
            }
        }
    }

    /**
     * 解析便携 SQLite 数据库文件的绝对路径
     *
     * 相对路径基于 data_dir/db 目录解析，与 DBConnection 原有行为保持一致
     */
    public static function resolvePortableFilename(string $name): string
    {
        if (FileSystem::isRelativePath($name)) {
            $name = zm_dir(config('global.data_dir') . '/db/' . $name);
            FileSystem::createDir(zm_dir(config('global.data_dir') . '/db'));
        }
        return $name;
    }

    /**
     * 创建一个便携的 SQLite 处理类
     *
     * @param  string      $name       SQLite 文件名
     * @param  bool        $create_new 如果数据库不存在，是否创建新的库
     * @param  bool        $keep_alive 是否复用连接（keep_alive 为 true 时按数据库文件接入有界连接池）
     * @throws DBException
     */
    public static function createPortableSqlite(string $name, bool $create_new = true, bool $keep_alive = true): DBWrapper
    {
        $options = ['dbType' => ZM_DB_PORTABLE, 'createNew' => $create_new, 'keepAlive' => $keep_alive];
        if ($keep_alive) {
            // keep_alive 语义升级为连接池：同一数据库文件复用有界数量的连接，协程之间互斥持有
            $options['pool'] = self::getPortablePool(self::resolvePortableFilename($name), $create_new);
        }
        return new DBWrapper($name, $options);
    }

    /**
     * 获取指定数据库文件的便携 SQLite 连接池
     *
     * 连接池按解析后的文件路径注册，采用 LRU 淘汰策略：常驻池数量达到上限时，
     * 淘汰最久未访问的池（池内空闲连接随对象销毁释放）。
     *
     * @throws DBException 文件不存在且 createNew 为 false 时抛出
     */
    private static function getPortablePool(string $filepath, bool $create_new): PoolInterface
    {
        if (!isset(self::$portable_pools[$filepath])) {
            $max = intval(config('global.database.portable_pool_max', self::PORTABLE_POOL_MAX));
            if ($max > 0 && count(self::$portable_pools) >= $max) {
                unset(self::$portable_pools[array_key_first(self::$portable_pools)]);
            }
            if (!$create_new && !file_exists($filepath)) {
                throw new DBException("Database file {$filepath} not found!");
            }
            self::$portable_pools[$filepath] = self::createPortablePool($filepath);
        } else {
            // 刷新访问顺序（LRU）
            $pool = self::$portable_pools[$filepath];
            unset(self::$portable_pools[$filepath]);
            self::$portable_pools[$filepath] = $pool;
        }
        return self::$portable_pools[$filepath];
    }

    private static function createPortablePool(string $filepath): PoolInterface
    {
        $size = intval(config('global.database.portable_pool_size', self::PORTABLE_POOL_SIZE));
        switch (Driver::getActiveDriverClass()) {
            case SwooleDriver::class:
                return new SwooleObjectPool($size, PortablePDO::class, $filepath, true);
            default:
                return new WorkermanObjectPool($size, PortablePDO::class, $filepath, true);
        }
    }
}
