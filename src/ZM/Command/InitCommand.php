<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use ZM\Exception\InitException;

class InitCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'init';

    private string $base_path;

    private bool $force = false;

    protected function configure(): void
    {
        $this->setDescription('Initialize framework starter | 初始化框架运行的基础文件');
        $this->setDefinition([
            new InputOption('force', 'F', null, '强制重制，覆盖现有文件'),
        ]);
        $this->setHelp('提取框架的基础文件到当前目录，以便于快速开始开发。');
    }

    protected function handle(): int
    {
        $this->setBasePath();
        $this->force = $this->input->getOption('force');

        $this->section('提取框架基础文件', function (ConsoleSectionOutput $section) {
            foreach ($this->getExtractFiles() as $file) {
                $section->write("<fg=gray>提取 {$file} ... </>");
                if ($this->shouldExtractFile($file)) {
                    try {
                        $this->extractFile($file);
                        $section->write('<info>完成</info>');
                    } catch (InitException $e) {
                        $section->write('<error>失败</error>');
                        throw $e;
                    } finally {
                        $section->writeln('');
                    }
                } else {
                    $section->writeln('<comment>跳过（已存在）</comment>');
                }
            }
        });

        if (LOAD_MODE === 1) {
            $this->section('应用自动加载配置', function (ConsoleSectionOutput $section) {
                $autoload = [
                    'psr-4' => [
                        'Module\\' => 'src/Module',
                        'Custom\\' => 'src/Custom',
                    ],
                    'files' => [
                        'src/Custom/global_function.php',
                    ],
                ];

                if (!file_exists($this->base_path . '/composer.json')) {
                    throw new InitException('未找到 composer.json 文件', '请检查当前目录是否为项目根目录', 41);
                }

                try {
                    $composer = json_decode(file_get_contents($this->base_path . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new InitException('解析 composer.json 文件失败', '请检查 composer.json 文件是否存在语法错误', 42, $e);
                }

                if (!isset($composer['autoload'])) {
                    $composer['autoload'] = $autoload;
                } else {
                    $composer['autoload'] = array_merge_recursive($composer['autoload'], $autoload);
                }

                try {
                    file_put_contents($this->base_path . '/composer.json', json_encode($composer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } catch (\JsonException $e) {
                    throw new InitException('写入 composer.json 文件失败', '', 0, $e);
                }

                $section->writeln('<fg=gray>执行 composer dump-autoload ...</>');
                exec('composer dump-autoload');

                $section->writeln('<info>完成</info>');
            });
        }

        // 将命令行入口标记为可执行
        chmod($this->base_path . '/zhamao', 0755);
        return 0;
    }

    private function getExtractFiles(): array
    {
        $patterns = [
            '/zhamao',
            '/.gitignore',
            '/config/*',
            '/src/Globals/*.php',
        ];

        $files = [];
        foreach ($patterns as $pattern) {
            // TODO: 优化代码，避免在循环中使用 array_merge 以减少资源消耗
            $files = array_merge($files, glob($this->getVendorPath($pattern)));
        }
        return array_map(function ($file) {
            return str_replace($this->getVendorPath(''), '', $file);
        }, $files);
    }

    /**
     * 设置基准目录
     */
    private function setBasePath(): void
    {
        $base_path = WORKING_DIR;
        if (file_exists($base_path . '/vendor/autoload.php')) {
            $this->base_path = $base_path;
        } else {
            $phar_link = new \Phar(__DIR__);
            $current_dir = pathinfo($phar_link->getPath())['dirname'];
            chdir($current_dir);
            $phar_link = 'phar://' . $phar_link->getPath();
            if (file_exists($phar_link . '/vendor/autoload.php')) {
                $this->base_path = $current_dir;
            } else {
                throw new InitException('框架启动模式不是 Composer 模式，无法进行初始化', '如果您是从 Github 下载的框架，请参阅文档进行源码模式启动', 42);
            }
        }
    }

    /**
     * 提取文件
     *
     * @param  string        $file 文件路径，相对于框架根目录
     * @throws InitException 提取失败时抛出异常
     */
    private function extractFile(string $file): void
    {
        $info = pathinfo($file);
        // 确保目录存在
        if (
            !file_exists($this->base_path . $info['dirname'])
            && !mkdir($concurrent_dir = $this->base_path . $info['dirname'], 0777, true)
            && !is_dir($concurrent_dir)
        ) {
            throw new InitException("无法创建目录 {$concurrent_dir}", '请检查目录权限');
        }

        if (copy($this->getVendorPath($file), $this->base_path . $file) === false) {
            throw new InitException("无法复制文件 {$file}", '请检查目录权限');
        }
    }

    private function shouldExtractFile(string $file): bool
    {
        return !file_exists($this->base_path . $file) || $this->force;
    }

    private function getVendorPath(string $file): string
    {
        try {
            $package_name = json_decode(file_get_contents(__DIR__ . '/../../../composer.json'), true, 512, JSON_THROW_ON_ERROR)['name'];
        } catch (\JsonException) {
            throw new InitException('无法读取框架包的 composer.json', '请检查框架包完整性，或者重新安装框架包');
        }
        return $this->base_path . '/vendor/' . $package_name . $file;
    }
}
