<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use ZM\Store\FileSystem;

#[AsCommand(name: 'build', description: '将项目构建一个 Phar 包')]
class BuildCommand extends Command
{
    use NonPharLoadModeOnly;

    /**
     * 配置
     */
    protected function configure()
    {
        $this->setHelp('此功能将会把整个项目打包为 Phar');
        $this->addOption('target', 'D', InputOption::VALUE_REQUIRED, '指定输出文件位置', 'zm.phar');
    }

    protected function handle(): int
    {
        $this->ensurePharWritable();

        $target = $this->input->getOption('target');
        if (FileSystem::isRelativePath($target)) {
            $target = SOURCE_ROOT_DIR . '/' . $target;
        }
        $this->ensureTargetWritable($target);
        $this->comment("目标文件：{$target}");

        $this->info('正在构建 Phar 包');

        $this->build($target, LOAD_MODE === LOAD_MODE_VENDOR ? 'src/entry.php' : 'vendor/zhamao/framework/src/entry.php');

        $this->info('Phar 包构建完成');

        return self::SUCCESS;
    }

    private function ensurePharWritable(): void
    {
        if (ini_get('phar.readonly') === '1') {
            if (!function_exists('pcntl_exec')) {
                $this->error('Phar 处于只读模式，且 pcntl 扩展未加载，无法自动切换到读写模式。');
                $this->error('请修改 php.ini 中的 phar.readonly 为 0，或执行 php -d phar.readonly=0 ' . $_SERVER['PHP_SELF'] . ' build');
                exit(1);
            }
            // Windows 下无法使用 pcntl_exec
            if (DIRECTORY_SEPARATOR === '\\') {
                $this->error('Phar 处于只读模式，且当前运行环境为 Windows，无法自动切换到读写模式。');
                $this->error('请修改 php.ini 中的 phar.readonly 为 0，或执行 php -d phar.readonly=0 ' . $_SERVER['PHP_SELF'] . ' build');
                exit(1);
            }
            $this->info('Phar 处于只读模式，正在尝试切换到读写模式...');
            sleep(1);
            $args = array_merge(['php', '-d', 'phar.readonly=0'], $_SERVER['argv']);
            if (pcntl_exec('/usr/bin/env', $args) === false) {
                $this->error('切换到读写模式失败，请检查环境。');
                exit(1);
            }
        }
    }

    private function ensureTargetWritable(string $target): void
    {
        if (file_exists($target) && !is_writable($target)) {
            $this->error('目标文件不可写：' . $target);
            exit(1);
        }
    }

    private function build(string $target, string $entry): void
    {
        $phar = new \Phar($target, 0, $target);

        $phar->startBuffering();
        $files = FileSystem::scanDirFiles(SOURCE_ROOT_DIR, true, true);
        $files = array_filter($files, function ($x) {
            $dirs = preg_match('/(^(bin|config|resources|src|vendor)\\/|^(composer\\.json|README\\.md)$)/', $x);
            return !($dirs !== 1);
        });
        sort($files);

        foreach ($this->progress()->iterate($files) as $file) {
            $phar->addFile($file, $file);
        }

        $phar->setStub(
            '#!/usr/bin/env php' . PHP_EOL .
            $phar::createDefaultStub($entry)
        );
        $phar->stopBuffering();
    }
}
