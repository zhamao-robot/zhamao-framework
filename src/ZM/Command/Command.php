<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\ZMException;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        return $this->handle();
    }

    abstract protected function handle(): int;

    protected function write(string $message, bool $newline = true): void
    {
        $this->output->write($message, $newline);
    }

    protected function info(string $message, bool $newline = true): void
    {
        $this->write("<info>{$message}</info>", $newline);
    }

    protected function error(string $message, bool $newline = true): void
    {
        $this->write("<error>{$message}</error>", $newline);
    }

    protected function comment(string $message, bool $newline = true): void
    {
        $this->write("<comment>{$message}</comment>", $newline);
    }

    protected function question(string $message, bool $newline = true): void
    {
        $this->write("<question>{$message}</question>", $newline);
    }

    protected function detail(string $message, bool $newline = true): void
    {
        $this->write("<fg=gray>{$message}</>", $newline);
    }

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
            exit(1);
        }
    }
}
