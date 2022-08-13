<?php

declare(strict_types=1);

namespace ZM;

use Exception;
use Phar;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Command\BuildCommand;
use ZM\Command\CheckConfigCommand;
use ZM\Command\Generate\SystemdGenerateCommand;
use ZM\Command\InitCommand;
use ZM\Command\Server\ServerReloadCommand;
use ZM\Command\Server\ServerStartCommand;
use ZM\Command\Server\ServerStatusCommand;
use ZM\Command\Server\ServerStopCommand;
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

        // 初始化命令
        $this->add(new ServerStatusCommand());      // server运行状态
        $this->add(new ServerReloadCommand());      // server重载
        $this->add(new ServerStopCommand());        // server停止
        $this->add(new ServerStartCommand());       // 运行主服务的指令控制器
        $this->add(new SystemdGenerateCommand());   // 生成systemd文件
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
}
