<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\ZMException;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
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
            return $this->handle();
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

    /**
     * 输出一段文本，默认样式
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     * @see OutputInterface::write()
     */
    protected function write(string $message, bool $newline = true): void
    {
        $this->output->write($message, $newline);
    }

    /**
     * 输出文本，一般用于提示信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    protected function info(string $message, bool $newline = true): void
    {
        $this->write("<info>{$message}</info>", $newline);
    }

    /**
     * 输出文本，一般用于错误信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    protected function error(string $message, bool $newline = true): void
    {
        $this->write("<error>{$message}</error>", $newline);
    }

    /**
     * 输出文本，一般用于警告或附注信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    protected function comment(string $message, bool $newline = true): void
    {
        $this->write("<comment>{$message}</comment>", $newline);
    }

    /**
     * 输出文本，一般用于提问信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    protected function question(string $message, bool $newline = true): void
    {
        $this->write("<question>{$message}</question>", $newline);
    }

    /**
     * 输出文本，一般用于详细信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    protected function detail(string $message, bool $newline = true): void
    {
        $this->write("<fg=gray>{$message}</>", $newline);
    }

    /**
     * 输出一个区块，区块内内容可以覆写
     *
     * 此功能需要 $output 为 {@see ConsoleOutputInterface} 类型
     *
     * @param string   $message  作为标题的文本
     * @param callable $callback 回调函数，接收一个参数，类型为 {@see ConsoleSectionOutput}
     */
    protected function section(string $message, callable $callback): void
    {
        $output = $this->output;
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('Section 功能只能在 ConsoleOutputInterface 中使用');
        }

        $this->info($message);
        $section = $output->section();
        try {
            $callback($section);
        } catch (ZMException $e) {
            $this->error($e->getMessage());
            exit(self::FAILURE);
        }
    }

    /**
     * 获取一个进度条实例
     *
     * @param int $max 最大进度值，可以稍后再设置
     */
    protected function progress(int $max = 0): ProgressBar
    {
        $progress = new ProgressBar($this->output, $max);
        $progress->setBarCharacter('<fg=green>⚬</>');
        $progress->setEmptyBarCharacter('<fg=red>⚬</>');
        $progress->setProgressCharacter('<fg=green>➤</>');
        $progress->setFormat(
            "%current%/%max% [%bar%] %percent:3s%%\n🪅 %estimated:-20s%  %memory:20s%" . PHP_EOL
        );
        return $progress;
    }
}
