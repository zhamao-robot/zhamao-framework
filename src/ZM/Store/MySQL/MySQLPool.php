<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace ZM\Store\MySQL;

use OneBot\Driver\Driver;
use OneBot\Driver\Interfaces\PoolInterface;
use OneBot\Driver\Swoole\ObjectPool as SwooleObjectPool;
use OneBot\Driver\Swoole\SwooleDriver;
use OneBot\Driver\Workerman\ObjectPool as WorkermanObjectPool;
use OneBot\Driver\Workerman\WorkermanDriver;
use PDO;
use RuntimeException;

class MySQLPool
{
    /**
     * @var array<string, PoolInterface> 连接池列表
     */
    private static $pools = [];

    /**
     * 通过配置文件创建一个 MySQL 连接池
     *
     * @throws MySQLException
     */
    public static function create(string $name, array $config)
    {
        $size = $config['pool_size'] ?? 128;
        $connect_str = 'mysql:host={host};port={port};dbname={dbname};charset={charset}';
        $table = [
            '{host}' => $config['host'],
            '{port}' => $config['port'],
            '{dbname}' => $config['dbname'],
            '{charset}' => $config['charset'] ?? 'utf8mb4',
        ];
        $connect_str = str_replace(array_keys($table), array_values($table), $connect_str);
        self::checkExtension();
        switch (Driver::getActiveDriverClass()) {
            case WorkermanDriver::class:
                self::$pools[$name] = new WorkermanObjectPool($size, PDO::class, $connect_str, $config['username'], $config['password'], $config['options'] ?? []);
                break;
            case SwooleDriver::class:
                self::$pools[$name] = new SwooleObjectPool($size, PDO::class, $connect_str, $config['username'], $config['password'], $config['options'] ?? []);
        }
    }

    /**
     * 获取一个数据库连接池
     *
     * @param string $name 连接池名称
     */
    public static function pool(string $name): PoolInterface
    {
        if (!isset(self::$pools[$name])) {
            throw new RuntimeException("Pool {$name} not found");
        }
        return self::$pools[$name];
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
     * @throws MySQLException
     */
    public static function checkExtension()
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
                throw new MySQLException(zm_internal_errcode('E00028') . '未安装 mysqlnd php-mysql扩展。');
            }
        }
    }
}
