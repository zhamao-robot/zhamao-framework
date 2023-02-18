<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    use CommandInteractTrait;

    /**
     * 输入
     */
    protected InputInterface $input;

    /**
     * 输出
     *
     * 一般来说同样会是 ConsoleOutputInterface
     */
    protected OutputInterface $output;

    /**
     * {@inheritdoc}
     * @internal 不建议覆写此方法，建议使用 {@see handle()} 方法
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        if ($this->shouldExecute()) {
            if (property_exists($this, 'bootstrappers')) {
                foreach ($this->bootstrappers as $bootstrapper) {
                    (new $bootstrapper())->bootstrap($this->input->getOptions());
                }
            }
            try {
                return $this->handle();
            } catch (\Throwable $e) {
                $msg = explode("\n", $e->getMessage());
                foreach ($msg as $v) {
                    $this->error($v);
                }
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }

    /**
     * 是否应该执行
     *
     * @return bool 返回 true 以继续执行，返回 false 以中断执行
     */
    protected function shouldExecute(): bool
    {
        return true;
    }

    /**
     * 命令的主体
     *
     * @return int 命令执行结果 {@see self::SUCCESS} 或 {@see self::FAILURE} 或 {@see self::INVALID}
     */
    abstract protected function handle(): int;
}
