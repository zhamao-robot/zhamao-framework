<?php

declare(strict_types=1);

namespace ZM;

use Exception;
use Phar;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZM\Command\BuildCommand;
use ZM\Command\CheckConfigCommand;
use ZM\Command\InitCommand;
use ZM\Exception\InitException;

/**
 * 命令行启动的入口文件，用于初始化环境变量，并启动命令行应用
 *
 * 这里启动的不是框架，而是框架相关的命令行环境
 */
final class ConsoleApplication extends Application
{
    private static $obj;

    /**
     * @throws InitException
     */
    public function __construct(string $name = 'zhamao-framework')
    {
        if (self::$obj !== null) {
            throw new InitException(zm_internal_errcode('E00069') . 'Initializing another Application is not allowed!');
        }

        // 初始化命令，请查看 ZM/Command 目录下的命令
        $this->registerConsoleCommands('ZM\Command', __DIR__ . '/Command', [
            // 抽象类：
            'Server/ServerCommand.php',
            // 有条件加载：
            'CheckConfigCommand.php',
            'BuildCommand.php',
            'InitCommand.php',
        ]);

        if (LOAD_MODE === 1) {                      // 如果是 Composer 模式加载的，那么可以输入 check:config 命令，检查配置文件是否需要更新
            $this->add(new CheckConfigCommand());
        }
        if (Phar::running() === '') {               // 不是 Phar 模式的话，可以执行打包解包初始化命令
            $this->add(new BuildCommand());         // 用于将整个应用打包为一个可执行的 phar
            $this->add(new InitCommand());          // 用于在 Composer 模式启动下，初始化脚手架文件
            // $this->add(new PluginPackCommand());    // 用于打包一个子模块为 phar 并进行分发
            // $this->add(new PluginListCommand());    // 用于列出已配置的子模块列表（存在 zm.json 文件的目录）
            // $this->add(new PluginUnpackCommand());  // 用于将打包好的 phar 模块解包到 src 目录中
        }

        // 初始化用户自定义命令
        // TODO: 提供更多配置项
        if (is_dir(SOURCE_ROOT_DIR . '/src/Commands')) {
            $this->registerConsoleCommands('Commands', SOURCE_ROOT_DIR . '/src/Commands');
        }

        self::$obj = $this; // 用于标记已经初始化完成
        parent::__construct($name, ZM_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        try {
            return parent::run($input, $output);
        } catch (Exception $e) {
            echo zm_internal_errcode('E00005') . "{$e->getMessage()} at {$e->getFile()}({$e->getLine()})" . PHP_EOL;
            exit(1);
        }
    }

    /**
     * 注册传入路径下的所有命令
     *
     * @param string $namespace     命令的命名空间，如 ZM\Command
     * @param string $path          命令的路径，如 __DIR__ . '/Command'
     * @param array  $exclude_files 忽略的文件，文件名以传入的 $path 为基准
     */
    private function registerConsoleCommands(string $namespace, string $path, array $exclude_files = []): void
    {
        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($path)->filter(
            function (SplFileInfo $file) use ($exclude_files) {
                return !in_array($file->getRelativePathname(), $exclude_files, true);
            }
        );

        foreach ($finder as $file) {
            $ns = $namespace;
            if ($relative = $file->getRelativePath()) {
                $ns .= '\\' . str_replace('/', '\\', $relative);
            }
            $class = $file->getBasename('.php');
            if ($namespace) {
                $ns .= '\\' . $class;
            } else {
                $ns = $class;
            }
            $this->add(new $ns());
        }
    }
}
