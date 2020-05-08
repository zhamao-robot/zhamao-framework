<?php


namespace ZM\Utils;


use Co;
use framework\Console;
use Framework\DataProvider;
use Framework\ZMBuf;
use ZM\API\CQ;

class ZMUtil
{
    /**
     * 检查workerStart是否运行结束
     */
    public static function checkWait() {
        if (ZMBuf::isset("wait_start")) {
            ZMBuf::append("wait_start", Co::getCid());
            Co::suspend();
        }
    }

    public static function stop($without_shutdown = false) {
        Console::info(Console::setColor("Stopping server...", "red"));
        foreach (ZMBuf::$server->connections as $v) {
            ZMBuf::$server->close($v);
        }
        DataProvider::saveBuffer();
        if (!$without_shutdown)
            ZMBuf::$server->shutdown();
        ZMBuf::$server->stop();
    }

    public static function getHttpCodePage(int $http_code) {
        if (isset(ZMBuf::globals("http_default_code_page")[$http_code])) {
            return Co::readFile(DataProvider::getResourceFolder() . "html/" . ZMBuf::globals("http_default_code_page")[$http_code]);
        } else return null;
    }

    public static function reload() {
        Console::info(Console::setColor("Reloading server...", "gold"));
        foreach (ZMBuf::get("wait_api", []) as $k => $v) {
            if ($v["result"] === null) Co::resume($v["coroutine"]);
        }
        foreach (ZMBuf::$server->connections as $v) {
            ZMBuf::$server->close($v);
        }
        DataProvider::saveBuffer();
        ZMBuf::$server->reload();
    }

    /**
     * 解析CQ码
     * @param $msg
     * @return array|null
     */
    static function getCQ($msg) {
        return CQ::getCQ($msg);
    }

    public static function getModInstance($class) {
        if (!isset(ZMBuf::$instance[$class])) {
            Console::debug("Class instance $class not exist, so I created it.");
            ZMBuf::$instance[$class] = new $class();
            return ZMBuf::$instance[$class];
        } else {
            return ZMBuf::$instance[$class];
        }
    }
}
