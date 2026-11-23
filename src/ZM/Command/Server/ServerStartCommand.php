<?php

declare(strict_types=1);

namespace ZM\Command\Server;

use OneBot\Driver\Process\ProcessManager;
use OneBot\Driver\Workerman\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\ZMKnownException;
use ZM\Framework;
use ZM\Logger\ConsoleLogger;
use ZM\Process\ProcessStateManager;

#[AsCommand(name: 'server', description: '启动服务器', aliases: ['server:start'])]
class ServerStartCommand extends ServerCommand
{
    /** @var array<string, false|resource> 守护进程的 stdio 句柄，必须保持引用，否则会被 GC 关掉 */
    private array $daemon_stdio = [];

    public function __destruct()
    {
        foreach ($this->daemon_stdio as $handle) {
            if (is_resource($handle)) {
                @fclose($handle);
            }
        }
        $this->daemon_stdio = [];
    }

    public static function exportOptionArray(): array
    {
        $cmd = new self();
        $cmd->configure();
        return array_map(fn ($x) => $x->getDefault(), $cmd->getDefinition()->getOptions());
    }

    protected function configure()
    {
        $this->setDefinition([
            new InputOption('driver', null, InputOption::VALUE_REQUIRED, '指定驱动类型'),
            new InputOption('daemon', null, null, '以守护进程的方式运行框架'),
            new InputOption('worker-num', null, InputOption::VALUE_REQUIRED, '启动框架时运行的 Worker 进程数量'),
            new InputOption('watch', null, null, '监听 plugins/ 目录下各个插件的文件变化并热更新（还不能用）'),
            new InputOption('disable-safe-exit', null, null, '关闭安全退出（关闭后按CtrlC时直接杀死进程）'),
            new InputOption('no-state-check', null, null, '关闭启动前框架运行状态检查'),
            new InputOption('private-mode', null, null, '启动时隐藏MOTD和敏感信息'),
            new InputOption('print-process-pid', null, null, '打印所有进程的PID'),
            new InputOption('disable-plugins', null, InputOption::VALUE_REQUIRED, '要禁用的插件，如需多个，采用逗号分割名称'),
        ]);
        $this->setHelp('直接运行可以启动');
    }

    /**
     * @throws ZMKnownException
     * @throws \Exception
     * @noinspection PhpComposerExtensionStubsInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 如果是支持多进程模式的，那么就检查框架进程的状态
        if (ProcessManager::isSupportedMultiProcess()) {
            $state = ProcessStateManager::getProcessState(ZM_PROCESS_MASTER);
            if (!$input->getOption('no-state-check')) {
                if (is_array($state) && posix_getsid($state['pid'] ?? -1) !== false) {
                    $output->writeln("<error>检测到已经在 pid: {$state['pid']} 进程启动了框架！</error>");
                    $output->writeln('<error>不可以同时启动两个框架！</error>');
                    return 1;
                }
            }
        }

        if ($input->getOption('driver')) {
            config(['global.driver' => $input->getOption('driver')]);
        }

        if ($input->getOption('worker-num')) {
            config(['global.swoole_options.swoole_set.worker_num' => (int) $input->getOption('worker-num')]);
            config(['global.workerman_options.workerman_worker_num' => (int) $input->getOption('worker-num')]);
        }

        // 守护进程：自己 fork + setsid，并把握手成功的 master.json 当作「就绪信号」。
        // 不用 Swoole 的 daemonize 是因为它会把 stdout/stderr 直接重定向到 /dev/null，
        // 守护进程从此没有任何日志（server:status 的 stdout 永远是空的）。
        $daemon_log = null;
        if ($input->getOption('daemon')) {
            $daemon_log = $this->daemonize();
        }

        if ($daemon_log !== null) {
            // 必须在 Framework::init() 之前注册：RegisterLogger 只在「未注册」时才装默认的 ConsoleLogger。
            // 这样守护模式下框架日志直接写文件，不依赖已经被 /dev/null 掉的 stdout。
            $handle = fopen($daemon_log, 'ab');
            if ($handle !== false) {
                ini_set('log_errors', '1');
                ini_set('error_log', $daemon_log);
                ob_logger_register(new ConsoleLogger($this->resolveLogLevel($input), $handle));
            }
        }

        // 框架启动的入口
        Framework::getInstance()->init($input->getOptions())->start();
        return 0;
    }

    /**
     * 把当前进程变成守护进程
     *
     * 父进程在确认子进程真的成为 master（master.json 里的 pid 等于子进程 pid）之后才退出，
     * 避免「命令返回了但服务还没起来」；子进程 setsid 脱离控制终端。
     *
     * @return string 守护日志路径（仅子进程会返回）
     */
    private function daemonize(): string
    {
        $log_file = zm_dir(WORKING_DIR . '/zm_data/logs/daemon.log');
        $dir = dirname($log_file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // 顺带让 MasterEventListener 把日志路径写进 master.json，server:status 才能 tail 它
        config([
            'global.daemon_log_file' => $log_file,                    // server:status 用它来 tail 日志
            'global.swoole_options.swoole_set.log_file' => $log_file, // Swoole 自身日志也写到同一文件
        ]);

        if (!function_exists('pcntl_fork') || !function_exists('posix_setsid') || PHP_OS_FAMILY === 'Windows') {
            // 没有 pcntl/posix：退回驱动自带的 daemonize（能脱离终端，但日志能力受限）
            config(['global.swoole_options.swoole_set.daemonize' => 1]);
            Worker::$daemonize = true;
            return $log_file;
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new ZMKnownException('E00081', '无法创建守护进程：fork 失败');
        }

        if ($pid > 0) {
            // 父进程：等子进程就绪后退出
            for ($i = 0; $i < 100; ++$i) {
                clearstatcache();
                $state = ProcessStateManager::getProcessState(ZM_PROCESS_MASTER);
                if (is_array($state) && (int) ($state['pid'] ?? 0) === $pid) {
                    exit(0);
                }
                if (function_exists('posix_kill') && !posix_kill($pid, 0)) {
                    fwrite(STDERR, '守护进程启动失败，请查看日志：' . $log_file . PHP_EOL);
                    exit(1);
                }
                usleep(100000);
            }
            fwrite(STDERR, '守护进程启动超时（10 秒内未就绪），请查看日志：' . $log_file . PHP_EOL);
            exit(1);
        }

        // 子进程：脱离控制终端
        posix_setsid();

        // 把 stdio 接到日志文件。Swoole 自带的 daemonize 会把 0/1/2 指向 /dev/null，
        // 于是端口占用、扩展缺失这类启动期错误只剩终端上一行、终端一关就没了。
        // 这里 fd 0/1/2 刚被关掉，接下来的 fopen 会依次占用它们。
        @fclose(STDIN);
        @fclose(STDOUT);
        @fclose(STDERR);
        $this->daemon_stdio = [
            'stdin' => fopen('/dev/null', 'rb'),
            'stdout' => fopen($log_file, 'ab'),
            'stderr' => fopen($log_file, 'ab'),
        ];

        return $log_file;
    }

    /**
     * 取日志等级参数（不同命令定义下该选项可能不存在）
     */
    private function resolveLogLevel(InputInterface $input): string
    {
        try {
            $level = (string) $input->getOption('log-level');
        } catch (\Throwable $e) {
            $level = '';
        }
        return $level !== '' ? $level : 'info';
    }
}
