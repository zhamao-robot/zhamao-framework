<?php

declare(strict_types=1);

namespace ZM\Command;

use Psr\Log\LogLevel;
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
     * System is unusable.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string  $message
     * @param mixed[] $context
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     */
    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $msg = match ($level) {
            'info' => "<info>{$message}</info>",
            'debug' => $this->input->getOption('verbose') ? "<fg=gray>{$message}</>" : '',
            'notice' => "<fg=cyan>{$message}</>",
            'warning' => "<comment>{$message}</comment>",
            'error', 'critical', 'alert', 'emergency' => "<error>{$message}</error>",
            default => '',
        };
        $msg = $this->interpolate($msg, $context);
        if ($msg !== '') {
            $this->output->write($msg, true);
        }
    }

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

    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $this->stringify($value);
        }

        return strtr($message, $replace);
    }

    private function stringify($item): string
    {
        switch (true) {
            case is_callable($item):
                if (is_array($item)) {
                    if (is_object($item[0])) {
                        return get_class($item[0]) . '@' . $item[1];
                    }
                    return $item[0] . '::' . $item[1];
                }
                return 'closure';
            case is_string($item):
                return $item;
            case is_array($item):
                return 'array' . (extension_loaded('json') ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS) : '');
            case is_object($item):
                return get_class($item);
            case is_resource($item):
                return 'resource(' . get_resource_type($item) . ')';
            case is_null($item):
                return 'null';
            case is_bool($item):
                return $item ? 'true' : 'false';
            case is_float($item):
            case is_int($item):
                return (string) $item;
            default:
                return 'unknown';
        }
    }
}
