<?php


namespace ZM\Utils;


use Exception;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Store\ZMBuf;
use ZM\Annotation\Swoole\SwooleEventAt;

class Terminal
{
    /**
     * @var false|resource
     */
    public static $console_proc = null;
    public static $pipes = [];

    static function listenConsole($terminal_id) {
        if ($terminal_id === null) {
            if (ZMBuf::$server->worker_id === 0) Console::info("ConsoleCommand disabled.");
            return;
        }
        global $terminal_id;
        global $port;
        $port = ZMConfig::get("global", "port");
        $vss = new SwooleEventAt();
        $vss->type = "open";
        $vss->level = 256;
        $vss->rule = "connectType:terminal";

        $vss->callback = function (?ConnectionObject $conn) use ($terminal_id) {
            $req = ctx()->getRequest();
            if ($conn->getName() != "terminal") return false;
            Console::debug("Terminal fd: " . $conn->getFd());
            ZMBuf::set("terminal_fd", $conn->getFd());
            if (($req->header["x-terminal-id"] ?? "") != $terminal_id) {
                ZMBuf::$server->close($conn->getFd());
                return false;
            }
            return false;
        };
        ZMBuf::$events[SwooleEventAt::class][] = $vss;
        $vss2 = new SwooleEventAt();
        $vss2->type = "message";
        $vss2->rule = "connectType:terminal";
        $vss2->callback = function (?ConnectionObject $conn) {
            if ($conn === null) return false;
            if ($conn->getName() != "terminal") return false;
            $cmd = ctx()->getFrame()->data;
            self::executeCommand($cmd);
            return false;
        };
        ZMBuf::$events[SwooleEventAt::class][] = $vss2;
        if (ZMBuf::$server->worker_id === 0) {
            go(function () {
                global $terminal_id, $port;
                $descriptorspec = array(
                    0 => STDIN,
                    1 => STDOUT,
                    2 => STDERR
                );
                self::$console_proc = proc_open('php -r \'$terminal_id = "' . $terminal_id . '";$port = ' . $port . ';require "' . __DIR__ . '/terminal_listener.php";\'', $descriptorspec, self::$pipes);
            });
        }
    }

    /**
     * @param string $cmd
     * @return bool
     */
    private static function executeCommand(string $cmd) {
        $it = explodeMsg($cmd);
        switch ($it[0] ?? '') {
            case 'logtest':
                Console::log(date("[H:i:s]") . " [L] This is normal msg. (0)");
                Console::error("This is error msg. (0)");
                Console::warning("This is warning msg. (1)");
                Console::info("This is info msg. (2)");
                Console::success("This is success msg. (2)");
                Console::verbose("This is verbose msg. (3)");
                Console::debug("This is debug msg. (4)");
                return true;
            case 'call':
                $class_name = $it[1];
                $function_name = $it[2];
                $class = new $class_name([]);
                call_user_func_array([$class, $function_name], []);
                return true;
            case 'bc':
                $code = base64_decode($it[1] ?? '', true);
                try {
                    eval($code);
                } catch (Exception $e) {
                }
                return true;
            case 'echo':
                Console::info($it[1]);
                return true;
            case 'color':
                Console::log($it[2], $it[1]);
                return true;
            case 'stop':
                ZMUtil::stop();
                return false;
            case 'reload':
            case 'r':
                ZMUtil::reload();
                return false;
            case 'save':
                //$origin = ZMBuf::$atomics["info_level"]->get();
                //ZMBuf::$atomics["info_level"]->set(3);
                DataProvider::saveBuffer();
                //ZMBuf::$atomics["info_level"]->set($origin);
                return true;
            case '':
                return true;
            default:
                Console::info("Command not found: " . $cmd);
                return true;
        }
    }
}
