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
use PDO;
use RuntimeException;
use ZM\Store\FileSystem;

class DBPool
{
    /**
     * @var array<string, SwooleObjectPool|WorkermanObjectPool> 连接池列表
     */
    private static $pools = [];

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
            default:
                throw new DBException('type ' . $config['type'] . ' not supported yet');
        }
        switch (Driver::getActiveDriverClass()) {
            case WorkermanDriver::class:
                self::$pools[$name] = new WorkermanObjectPool($size, PDO::class, $connect_str, ...$args);
                break;
            case SwooleDriver::class:
                self::$pools[$name] = new SwooleObjectPool($size, PDO::class, $connect_str, ...$args);
        }
    }

    /**
     * 获取一个数据库连接池
     *
     * @param  string                               $name 连接池名称
     * @return SwooleObjectPool|WorkermanObjectPool
     */
    public static function pool(string $name)
    {
        if (!isset(self::$pools[$name]) && count(self::$pools) !== 1) {
            throw new RuntimeException("Pool {$name} not found");
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
}
