<?php

declare(strict_types=1);

namespace ZM\Store\Lock;

use ZM\Exception\ZMKnownException;

class FileLock
{
    private static $lock_file_handle = [];

    private static $name_hash = [];

    /**
     * 基于文件的锁，适用于跨进程操作资源用的
     *
     * @throws ZMKnownException
     */
    public static function lock(string $name)
    {
        self::$name_hash[$name] = self::$name_hash[$name] ?? md5($name);
        $lock_file = zm_dir(TMP_DIR . '/.zm_' . zm_instance_id() . self::$name_hash[$name] . '.lock');
        self::$lock_file_handle[$name] = fopen($lock_file, 'w');
        if (self::$lock_file_handle[$name] === false) {
            logger()->critical("Can not create lock file {$lock_file}\n");
            throw new ZMKnownException('E99999', 'Can not create lock file ' . $lock_file);
        }
        if (!flock(self::$lock_file_handle[$name], LOCK_EX)) {
            logger()->error("File Lock \"{$name}\"already exists.\n");
        }
    }

    /**
     * 解锁
     *
     * @param string $name 锁名
     */
    public static function unlock(string $name)
    {
        if ((self::$lock_file_handle[$name] ?? false) !== false) {
            fclose(self::$lock_file_handle[$name]);
        }
    }
}
