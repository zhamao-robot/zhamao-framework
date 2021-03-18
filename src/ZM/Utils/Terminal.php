<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use Exception;
use Psy\Shell;
use Swoole\Event;
use ZM\Console\Console;
use ZM\Framework;

class Terminal
{
    /**
     * @param string $cmd
     * @param $resource
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUnused
     */
    public static function executeCommand(string $cmd, $resource) {
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
                $class->$function_name();
                return true;
            case 'psysh':
                if (Framework::$argv["disable-coroutine"]) {
                    (new Shell())->run();
                } else
                    Console::error("Only \"--disable-coroutine\" mode can use psysh!!!");
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
                Event::del($resource);
                ZMUtil::stop();
                return false;
            case 'reload':
            case 'r':
                ZMUtil::reload();
                return false;
            case '':
                return true;
            default:
                Console::info("Command not found: " . $cmd);
                return true;
        }
    }
}
