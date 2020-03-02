<?php


namespace Framework;


use Swoole\Coroutine\System;

class Logger
{
    private static function getTimeFormat($type = "I") {
        return "[" . date("Y-m-d H:i:s") . "]\t[" . $type . "]\t";
    }

    public static function writeSwooleLog($log) {
        System::writeFile(CRASH_DIR . "swoole_error.log", "\n" . self::getTimeFormat() . $log, FILE_APPEND);
    }
}