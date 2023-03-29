<?php

declare(strict_types=1);

namespace ZM\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\InitException;
use ZM\Utils\ZMUtil;

#[AsCommand(name: 'init', description: '初始化框架运行的基础文件')]
class InitCommand extends Command
{
    use NonPharLoadModeOnly;

    private string $base_path;

    private bool $force = false;

    protected function configure(): void
    {
        $this->setDefinition([
            new InputOption('force', 'f', InputOption::VALUE_NONE, '覆盖现有文件'),
            new InputOption('docker', null, InputOption::VALUE_NONE, '启用 Docker 支持'),
        ]);
        $this->setHelp('提取框架的基础文件到当前目录，以便于快速开始开发。');
    }

    protected function handle(): int
    {
        $this->setBasePath();
        $this->force = $this->input->getOption('force');

        $this->section('提取框架基础文件', function (ConsoleSectionOutput $section) {
            $this->extractFiles($this->getExtractFiles(), $section);
        });

        if (LOAD_MODE === LOAD_MODE_SRC) {
            $this->section('应用自动加载配置', function (ConsoleSectionOutput $section) {
                $autoload = [
                    'psr-4' => [
                        'Module\\' => 'src/Module',
                        'Custom\\' => 'src/Custom',
                    ],
                    'files' => [],
                ];

                $section->write('<fg=gray>更新 composer.json ... </>');

                if (!file_exists($this->base_path . '/composer.json')) {
                    throw new InitException('未找到 composer.json 文件', 41);
                }

                try {
                    $composer = json_decode(file_get_contents($this->base_path . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new InitException('解析 composer.json 文件失败', 42, $e);
                }

                if (!isset($composer['autoload'])) {
                    $composer['autoload'] = $autoload;
                } else {
                    $composer['autoload'] = array_merge($composer['autoload'], $autoload);
                }

                try {
                    file_put_contents($this->base_path . '/composer.json', json_encode($composer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } catch (\JsonException $e) {
                    throw new InitException('写入 composer.json 文件失败', 0, $e);
                }

                $section->writeln('<info>完成</info>');
                $section->write('<fg=gray>执行 composer dump-autoload ... </>');

                // 兼容内建 Composer
                $env = ZMUtil::getComposerExecutable();
                passthru(PHP_BINARY . ' ' . escapeshellcmd($env) . ' dump-autoload');

                $section->writeln('<info>完成</info>');
            });
        }

        if ($this->input->getOption('docker')) {
            $this->section('应用 Docker 支持', function (ConsoleSectionOutput $section) {
                $files = $this->getFilesFromPatterns([
                    '/docker/*/Dockerfile',
                    '/docker/environment.env.example',
                    '/docker-compose.yml',
                ]);
                $this->extractFiles($files, $section);

                // 生成 env 文件
                if ($this->shouldExtractFile('/docker/environment.env')) {
                    $section->write('<fg=gray>生成环境变量文件 ... </>');
                    $env = file_get_contents($this->base_path . '/docker/environment.env.example');
                    foreach ($this->getEnvVariables() as $key => $value) {
                        $env = $this->injectEnv($env, $key, $value);
                    }
                    file_put_contents($this->base_path . '/docker/environment.env', $env);
                    $section->writeln('<info>完成</info>');
                } else {
                    $section->writeln('<fg=gray>生成环境变量文件 ... </><comment>跳过（已存在）</comment>');
                }
            });
        }

        // 将命令行入口标记为可执行
        chmod($this->base_path . '/zhamao', 0755);
        return self::SUCCESS;
    }

    private function getExtractFiles(): array
    {
        $patterns = [
            '/zhamao',
            '/.gitignore',
            '/config/*',
        ];

        return $this->getFilesFromPatterns($patterns);
    }

    private function getFilesFromPatterns(array $patterns): array
    {
        $files = [];
        foreach ($patterns as $pattern) {
            // TODO: 优化代码，避免在循环中使用 array_merge 以减少资源消耗
            $files = array_merge($files, glob($this->getVendorPath($pattern)));
        }
        return array_map(fn ($file) => str_replace($this->getVendorPath(''), '', $file), $files);
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
                throw new InitException('框架启动模式不是 Composer 模式，无法进行初始化', 42);
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
            throw new InitException("无法创建目录 {$concurrent_dir}");
        }

        if (copy($this->getVendorPath($file), $this->base_path . $file) === false) {
            throw new InitException("无法复制文件 {$file}");
        }
    }

    private function shouldExtractFile(string $file): bool
    {
        return !file_exists($this->base_path . $file) || $this->force;
    }

    private function getVendorPath(string $file): string
    {
        return FRAMEWORK_ROOT_DIR . $file;
    }

    private function extractFiles(array $files, OutputInterface $output): void
    {
        foreach ($files as $file) {
            $output->write("<fg=gray>提取 {$file} ... </>");
            if ($this->shouldExtractFile($file)) {
                try {
                    $this->extractFile($file);
                    $output->write('<info>完成</info>');
                } catch (InitException $e) {
                    $output->write('<error>失败</error>');
                    throw $e;
                } finally {
                    $output->writeln('');
                }
            } else {
                $output->writeln('<comment>跳过（已存在）</comment>');
            }
        }
    }

    private function injectEnv(string $env, string $key, string $value): string
    {
        $pattern = "/^{$key}=.+$/m";
        if (preg_match($pattern, $env)) {
            return preg_replace($pattern, "{$key}={$value}", $env);
        }

        return $env . PHP_EOL . "{$key}={$value}";
    }

    private function getEnvVariables(): array
    {
        return [
            'REDIS_PASSWORD' => bin2hex(random_bytes(8)),

            'POSTGRES_USER' => 'root',
            'POSTGRES_PASSWORD' => bin2hex(random_bytes(8)),

            'POSTGRES_APPLICATION_DATABASE' => 'zhamao',
            'POSTGRES_APPLICATION_USER' => 'zhamao',
            'POSTGRES_APPLICATION_USER_PASSWORD' => bin2hex(random_bytes(8)),
        ];
    }
}
