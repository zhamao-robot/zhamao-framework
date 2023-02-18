<?php

declare(strict_types=1);

namespace ZM;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Command\Server\ServerStartCommand;
use ZM\Exception\SingletonViolationException;
use ZM\Store\FileSystem;

/**
 * 命令行启动的入口文件，用于初始化环境变量，并启动命令行应用
 *
 * 这里启动的不是框架，而是框架相关的命令行环境
 */
final class ConsoleApplication extends Application
{
    protected array $bootstrappers = [
        Bootstrap\LoadConfiguration::class,         // 加载配置文件
        Bootstrap\LoadGlobalDefines::class,         // 加载框架级别的全局常量声明
        Bootstrap\RegisterLogger::class,            // 加载 Logger
        Bootstrap\HandleExceptions::class,          // 注册异常处理器
        Bootstrap\RegisterEventProvider::class,     // 绑定框架的 EventProvider 到 libob 的 Driver 上
        Bootstrap\SetInternalTimezone::class,       // 设置时区
    ];

    private static ?ConsoleApplication $obj = null;

    public function __construct(string $name = 'zhamao-framework')
    {
        if (self::$obj !== null) {
            throw new SingletonViolationException(self::class);
        }

        // 初始化命令
        $command_classes = [];
        // 先加载框架内置命令
        $command_classes = array_merge(
            $command_classes,
            FileSystem::getClassesPsr4(FRAMEWORK_ROOT_DIR . '/src/ZM/Command', 'ZM\\Command')
        );
        // 再加载用户自定义命令（如存在）
        if (is_dir(SOURCE_ROOT_DIR . '/src/Command')) {
            $command_classes = array_merge(
                $command_classes,
                FileSystem::getClassesPsr4(SOURCE_ROOT_DIR . '/src/Command', 'Command')
            );
        }
        // 初始化 Composer 变量
        if (file_exists(WORKING_DIR . '/runtime/composer.phar')) {
            echo '* Using native composer' . PHP_EOL;
            putenv('COMPOSER_EXECUTABLE=' . WORKING_DIR . '/runtime/composer.phar');
        }
        $commands = [];
        foreach ($command_classes as $command_class) {
            try {
                $command_class_ref = new \ReflectionClass($command_class);
            } catch (\ReflectionException $e) {
                logger()->error("命令 {$command_class} 无法加载！反射失败：" . $e->getMessage());
                continue;
            }
            if ($command_class_ref->isAbstract()) {
                continue;
            }
            // 从 AsCommand 注解中获取命令名称
            $attr = $command_class_ref->getAttributes(AsCommand::class);
            if (count($attr) > 0) {
                $commands[$attr[0]->getArguments()['name']] = fn () => new $command_class();
            } else {
                logger()->warning("命令 {$command_class} 没有使用 AsCommand 注解，无法被加载");
            }
        }
        // 命令工厂，用于延迟加载命令
        $command_loader = new FactoryCommandLoader($commands);
        $this->setCommandLoader($command_loader);

        self::$obj = $this; // 用于标记已经初始化完成
        parent::__construct($name, ZM_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $options = $input?->getOptions() ?? ServerStartCommand::exportOptionArray();
        foreach ($this->bootstrappers as $bootstrapper) {
            resolve($bootstrapper)->bootstrap($options);
        }

        try {
            return parent::run($input, $output);
        } catch (\Exception $e) {
            echo zm_internal_errcode('E00005') . "{$e->getMessage()} at {$e->getFile()}({$e->getLine()})" . PHP_EOL;
            exit(1);
        }
    }
}
