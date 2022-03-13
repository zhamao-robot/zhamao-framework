<?php /** @noinspection PhpUnused */


namespace ZM\Utils\Manager;


use Swoole\Process;

class ProcessManager
{
    /** @var Process[] */
    public static $user_process = [];

    /**
     * @param string $name
     * @param callable $callable
     * @return Process
     */
    public static function createUserProcess(string $name, callable $callable): Process
    {
        return self::$user_process[$name] = new Process($callable);
    }

    /**
     * @param string $string
     * @return Process|null
     */
    public static function getUserProcess(string $string): ?Process
    {
        return self::$user_process[$string] ?? null;
    }
}