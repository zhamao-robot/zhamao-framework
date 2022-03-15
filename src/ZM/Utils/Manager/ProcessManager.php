<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace ZM\Utils\Manager;

use Swoole\Process;

class ProcessManager
{
    /** @var Process[] */
    public static $user_process = [];

    public static function createUserProcess(string $name, callable $callable): Process
    {
        return self::$user_process[$name] = new Process($callable);
    }

    public static function getUserProcess(string $string): ?Process
    {
        return self::$user_process[$string] ?? null;
    }
}
