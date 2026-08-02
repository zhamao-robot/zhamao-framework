<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use ZM\Store\FileSystem;

/**
 * 便携 SQLite 连接对象
 *
 * 在原生 PDO 之上补充文件存在性检查与常用 PRAGMA 配置（WAL 日志模式、busy 超时），
 * 供 DBPool 的便携 SQLite 连接池与临时连接共同使用。
 */
class PortablePDO extends \PDO
{
    public function __construct(string $filepath, bool $create_new = true)
    {
        if (!file_exists($filepath)) {
            if (!$create_new) {
                throw new DBException("Database file {$filepath} not found!");
            }
            // 确保数据库文件所在目录存在
            FileSystem::createDir(dirname($filepath));
        }
        parent::__construct('sqlite:' . $filepath);
        // WAL 日志模式：同一数据库文件支持多个连接并发读写
        $this->exec('PRAGMA journal_mode = WAL');
        // busy 超时：多连接写竞争时等待锁释放，而不是立即失败
        $this->exec('PRAGMA busy_timeout = 5000');
    }
}
