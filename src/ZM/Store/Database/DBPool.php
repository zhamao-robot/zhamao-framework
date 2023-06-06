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
     * @var array<string, SwooleObjectPool|WorkermanObjectPool> 连接池列表
     */
    private static array $pools = [];

    /**
     * @var array<string, DBWrapper> 持久化的便携 SQLite 连接对象缓存
     */
    private static array $portable_cache = [];

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
     * 重新初始化所有的便携 SQLite 连接（其实就是断开）
     */
    public static function resetPortableSQLite(): void
    {
        foreach (self::$portable_cache as $name => $wrapper) {
            unset(self::$portable_cache[$name]);
        }
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
     * 创建一个便携的 SQLite 处理类
     *
     * @param  string      $name       SQLite 文件名
     * @param  bool        $create_new 如果数据库不存在，是否创建新的库
     * @throws DBException
     */
    public static function createPortableSqlite(string $name, bool $create_new = true, bool $keep_alive = true): DBWrapper
    {
        if ($keep_alive && isset(self::$portable_cache[$name])) {
            return self::$portable_cache[$name];
        }
        $db = new DBWrapper($name, ['dbType' => ZM_DB_PORTABLE, 'createNew' => $create_new]);
        if ($keep_alive) {
            self::$portable_cache[$name] = $db;
        }
        return $db;
    }
}
