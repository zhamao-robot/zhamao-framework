<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use ZM\Exception\FileSystemException;
use ZM\Store\FileSystem;
use ZM\Store\PharHelper;

#[AsCommand(name: 'build', description: '将炸毛框架项目构建一个 Phar 包')]
class BuildCommand extends Command
{
    use NonPharLoadModeOnly;

    /**
     * 配置
     */
    protected function configure()
    {
        $this->setHelp('此功能将会把整个框架项目打包为 Phar' . PHP_EOL . '默认会启用压缩功能，通过去除文件中的注释和空格，以减小文件大小，但可能增加构建耗时');
        $this->addOption('build-dir', 'D', InputOption::VALUE_REQUIRED, '指定输出文件夹（默认为 build/）', WORKING_DIR . '/build');
        $this->addOption('target', 'T', InputOption::VALUE_REQUIRED, '指定输出文件名称（默认为 zm.phar）', 'zm.phar');
        $this->addOption('no-compress', null, InputOption::VALUE_NONE, '是否不压缩文件，以减小构建耗时');
    }

    /**
     * @throws \PharException
     * @throws FileSystemException
     */
    protected function handle(): int
    {
        // 确认可写 Phar
        PharHelper::ensurePharWritable();

        $target = $this->input->getOption('target');
        $build_dir = $this->input->getOption('build-dir');
        if (FileSystem::isRelativePath($build_dir)) {
            $build_dir = WORKING_DIR . '/' . $build_dir;
        }
        $target = $build_dir . '/' . $target;

        // 确认目标文件可写
        FileSystem::createDir($build_dir);
        FileSystem::ensureFileWritable($target);

        $this->comment("目标文件：{$target}");

        if (file_exists($target)) {
            $this->comment('目标文件已存在，正在删除...');
            unlink($target);
        }

        // 检查是否安装了开发依赖
        if ((LOAD_MODE === LOAD_MODE_SRC) && file_exists(SOURCE_ROOT_DIR . '/vendor/composer/installed.php')) {
            $installed = require SOURCE_ROOT_DIR . '/vendor/composer/installed.php';
            if ($installed['root']['dev']) {
                $this->comment('检测到当前项目安装了开发依赖，这对于构建的 Phar 包来说是不必要的，建议在构建前执行 composer install --no-dev 以缩减包体积及加快构建速度');
                $this->confirmOrExit('是否继续构建？');
            }
        }

        $this->info('正在构建 Phar 包');

        $this->build(
            $target,
            LOAD_MODE === LOAD_MODE_SRC ? 'src/entry.php' : 'vendor/zhamao/framework/src/entry.php',
        );

        $this->info('Phar 包构建完成');

        return self::SUCCESS;
    }

    private function build(string $target, string $entry): void
    {
        $phar = new \Phar($target, 0);

        $phar->startBuffering();
        $files = FileSystem::scanDirFiles(SOURCE_ROOT_DIR, true, true);
        $separator = '\\' . DIRECTORY_SEPARATOR;
        // 只打包 bin / config / resources / src / vendor 目录以及 composer.json / composer.lock / entry.php
        $files = array_filter($files, function ($file) use ($separator) {
            return preg_match('/^(bin|config|resources|src|vendor)' . $separator . '|^(composer\\.json|README\\.md)$/', $file);
        });
        sort($files);

        if ($this->input->getOption('no-compress')) {
            foreach ($this->progress()->iterate($files) as $file) {
                $phar->addFile($file, $file);
            }
        } else {
            foreach ($this->progress()->iterate($files) as $file) {
                $phar->addFromString($file, php_strip_whitespace($file));
            }
        }

        $phar->setStub(
            '#!/usr/bin/env php' . PHP_EOL .
            $phar::createDefaultStub($entry)
        );
        $phar->stopBuffering();
    }
}
