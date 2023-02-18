<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use ZM\Exception\ZMException;

/**
 * @property InputInterface  $input
 * @property OutputInterface $output
 */
trait CommandInteractTrait
{
    /**
     * 输出一段文本，默认样式
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     * @see OutputInterface::write()
     */
    public function write(string $message, bool $newline = true): void
    {
        $this->output->write($message, $newline);
    }

    /**
     * 输出文本，一般用于提示信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    public function info(string $message, bool $newline = true): void
    {
        $this->write("<info>{$message}</info>", $newline);
    }

    /**
     * 输出文本，一般用于错误信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    public function error(string $message, bool $newline = true): void
    {
        $this->write("<error>{$message}</error>", $newline);
    }

    /**
     * 输出文本，一般用于警告或附注信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    public function comment(string $message, bool $newline = true): void
    {
        $this->write("<comment>{$message}</comment>", $newline);
    }

    /**
     * 输出文本，一般用于提问信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    public function question(string $message, bool $newline = true): void
    {
        $this->write("<question>{$message}</question>", $newline);
    }

    /**
     * 输出文本，一般用于详细信息
     *
     * @param string $message 要输出的文本
     * @param bool   $newline 是否在文本后换行
     */
    public function detail(string $message, bool $newline = true): void
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
    public function section(string $message, callable $callback): void
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
    public function progress(int $max = 0): ProgressBar
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

    /**
     * 询问用户是否确认
     *
     * @param  string $prompt  提示信息
     * @param  bool   $default 默认值
     * @return bool   用户是否确认
     */
    public function confirm(string $prompt, bool $default = true): bool
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $affix = $default ? '[Y/n]' : '[y/N]';

        $question = new ConfirmationQuestion("{$prompt} {$affix} ", $default);
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * 询问用户是否确认，否则退出
     *
     * @param string $prompt  提示信息
     * @param bool   $default 默认值
     */
    public function confirmOrExit(string $prompt, bool $default = true): void
    {
        if (!$this->confirm($prompt, $default)) {
            exit(self::SUCCESS);
        }
    }
}
