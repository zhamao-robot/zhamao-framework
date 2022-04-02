<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace ZM\Utils;

use Doctrine\Common\Annotations\AnnotationReader;
use Error;
use Exception;
use ReflectionClass;
use Swoole\Process;
use ZM\Annotation\Command\TerminalCommand;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;

class Terminal
{
    public static $default_commands = false;

    /**
     * @throws Exception
     * @throws Error
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpUnused
     */
    public static function executeCommand(string $cmd)
    {
        if (self::$default_commands === false) {
            self::init();
        }
        $it = explode_msg($cmd);
        $dispatcher = new EventDispatcher(TerminalCommand::class);
        $dispatcher->setRuleFunction(function ($v) use ($it) {
            /* @var TerminalCommand $v */
            return !empty($it) && ($v->command == $it[0] || $v->alias == $it[0]);
        });
        $dispatcher->setReturnFunction(function () {
            EventDispatcher::interrupt('none');
        });
        $dispatcher->dispatchEvents($it);
        if ($dispatcher->store !== 'none' && $cmd !== '') {
            Console::info('Command not found: ' . $cmd);
            return true;
        }
        return false;
    }

    public static function log($type, $log_msg)
    {
        ob_start();
        if (!in_array($type, ['log', 'info', 'debug', 'success', 'warning', 'error', 'verbose'])) {
            ob_get_clean();
            return;
        }
        Console::$type($log_msg);
        $r = ob_get_clean();
        $all = ManagerGM::getAllByName('terminal');
        foreach ($all as $v) {
            server()->send($v->getFd(), "\r" . $r);
            server()->send($v->getFd(), '>>> ');
        }
    }

    public static function init()
    {
        Console::debug('Initializing Terminal...');
        foreach ((EventManager::$events[TerminalCommand::class] ?? []) as $v) {
            if ($v->command == 'help') {
                self::$default_commands = true;
                break;
            }
        }
        $class = new Terminal();
        $reader = new AnnotationReader();
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods() as $v) {
            $r = $reader->getMethodAnnotation($v, TerminalCommand::class);
            if ($r !== null) {
                Console::debug('adding command ' . $r->command);
                $r->class = Terminal::class;
                $r->method = $v->getName();
                EventManager::addEvent(TerminalCommand::class, $r);
            }
        }
        self::$default_commands = true;
    }

    /**
     * @TerminalCommand(command="help",alias="h",description="显示帮助菜单")
     */
    public function help()
    {
        $help = [];
        foreach ((EventManager::$events[TerminalCommand::class] ?? []) as $v) {
            /** @var TerminalCommand $v */
            $cmd = $v->command . ($v->alias !== '' ? (' | ' . $v->alias) : '');
            $painted = Console::setColor($v->command, 'green') . ($v->alias !== '' ? (' | ' . Console::setColor($v->alias, 'green')) : '');
            $help[] = $painted . ':' . str_pad('', 16 - strlen($cmd) - 1) . ($v->description === '' ? '<无描述>' : $v->description);
        }
        echo implode("\n", $help) . PHP_EOL;
    }

    /**
     * @TerminalCommand(command="status",description="显示Swoole Server运行状态（需要安装league/climate组件）")
     */
    public function status()
    {
        if (class_exists('\\League\\CLImate\\CLImate')) {
            $class = '\\League\\CLImate\\CLImate';
            $climate = new $class();
            $climate->output->addDefault('buffer');

            $objs = server()->stats();
            $climate->columns($objs);
            $obj = $climate->output->get('buffer')->get();
            $climate->output->get('buffer')->clean();
            echo $obj;
            return;
        }
        Console::warning('你还没有安装 league/climate 组件，无法使用此功能！');
    }

    /**
     * @TerminalCommand(command="logtest",description="测试log的显示等级")
     */
    public function testlog()
    {
        Console::log(date('[H:i:s]') . ' [L] This is normal msg. (0)');
        Console::error('This is error msg. (0)');
        Console::warning('This is warning msg. (1)');
        Console::info('This is info msg. (2)');
        Console::success('This is success msg. (2)');
        Console::verbose('This is verbose msg. (3)');
        Console::debug('This is debug msg. (4)');
    }

    /**
     * @TerminalCommand(command="call",description="用于执行不需要参数的动态函数，比如 `call \Module\Example\Hello hitokoto`")
     */
    public function call(array $it)
    {
        $class_name = $it[1];
        $function_name = $it[2];
        $class = new $class_name([]);
        $r = $class->{$function_name}();
        if (is_string($r)) {
            Console::success($r);
        }
    }

    /**
     * @TerminalCommand(command="level",description="设置log等级，例如 `level 0|1|2|3|4`")
     */
    public function level(array $it)
    {
        $level = intval(is_numeric($it[1] ?? 99) ? ($it[1] ?? 99) : 99);
        if ($level > 4 || $level < 0) {
            Console::warning("Usage: 'level 0|1|2|3|4'");
        } else {
            Console::setLevel($level) || Console::success('Success!!');
        }
    }

    /**
     * @TerminalCommand(command="bc",description="eval执行代码，但输入必须是将代码base64之后的，如 `bc em1faW5mbygn5L2g5aW9Jyk7`")
     */
    public function bc(array $it)
    {
        $code = base64_decode($it[1] ?? '', true);
        try {
            eval($code);
        } catch (Exception $e) {
        }
    }

    /**
     * @TerminalCommand(command="echo",description="输出内容，用法：`echo hello`")
     */
    public function echoI(array $it)
    {
        Console::info($it[1]);
    }

    /**
     * @TerminalCommand(command="stop",description="停止框架")
     */
    public function stop()
    {
        posix_kill(server()->master_pid, SIGTERM);
    }

    /**
     * @TerminalCommand(command="reload",alias="r",description="重启框架（重载用户代码）")
     */
    public function reload()
    {
        Process::kill(server()->master_pid, SIGUSR1);
    }
}
