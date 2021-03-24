<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use Exception;
use Psy\Shell;
use ZM\Annotation\Command\TerminalCommand;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Framework;

class Terminal
{
    /**
     * @param string $cmd
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUnused
     * @throws Exception
     */
    public static function executeCommand(string $cmd) {
        $it = explodeMsg($cmd);
        switch ($it[0] ?? '') {
            case 'help':
                $help[] = "exit | q:\t断开远程终端";
                $help[] = "logtest:\t输出所有可以打印的log等级示例消息，用于测试Console";
                $help[] = "call:\t\t用于执行不需要参数的动态函数，比如 `call \Module\Example\Hello hitokoto`";
                $help[] = "level:\t\t设置log等级，例如 `level 0|1|2|3|4`";
                $help[] = "bc:\t\teval执行代码，但输入必须是将代码base64之后的，如 `bc em1faW5mbygn5L2g5aW9Jyk7`";
                $help[] = "stop:\t\t停止服务器";
                $help[] = "reload | r:\t热重启用户编写的模块代码";
                foreach((EventManager::$events[TerminalCommand::class] ?? []) as $v) {
                    $help[]=$v->command.":\t\t".(empty($v->description) ? "<用户自定义指令>" : $v->description);
                }
                echo implode("\n", $help) . PHP_EOL;
                return true;
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
                $r = $class->$function_name();
                if (is_string($r)) Console::success($r);
                return true;
            case 'psysh':
                if (Framework::$argv["disable-coroutine"]) {
                    (new Shell())->run();
                } else
                    Console::error("Only \"--disable-coroutine\" mode can use psysh!!!");
                return true;
            case 'level':
                $level = intval(is_numeric($it[1] ?? 99) ? ($it[1] ?? 99) : 99);
                if ($level > 4 || $level < 0) Console::warning("Usage: 'level 0|1|2|3|4'");
                else Console::setLevel($level) || Console::success("Success!!");
                break;
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
            case '':
                return true;
            default:
                $dispatcher = new EventDispatcher(TerminalCommand::class);
                $dispatcher->setRuleFunction(function ($v) use ($it) {
                    /** @var TerminalCommand $v */
                    return $v->command == $it[0];
                });
                $dispatcher->setReturnFunction(function () {
                    EventDispatcher::interrupt('none');
                });
                $dispatcher->dispatchEvents($it);
                if ($dispatcher->store !== 'none') {
                    Console::info("Command not found: " . $cmd);
                    return true;
                }
        }
        return false;
    }

    public static function log($type, $log_msg) {
        ob_start();
        if (!in_array($type, ["log", "info", "debug", "success", "warning", "error", "verbose"])) {
            ob_get_clean();
            return;
        }
        Console::$type($log_msg);
        $r = ob_get_clean();
        $all = ManagerGM::getAllByName("terminal");
        foreach ($all as $k => $v) {
            server()->send($v->getFd(), "\r" . $r);
            server()->send($v->getFd(), ">>> ");
        }
    }
}
