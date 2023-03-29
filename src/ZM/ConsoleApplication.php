<?php

declare(strict_types=1);

namespace ZM;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZM\Exception\SingletonViolationException;
use ZM\Store\FileSystem;

/**
 * 命令行启动的入口文件，用于初始化环境变量，并启动命令行应用
 *
 * 这里启动的不是框架，而是框架相关的命令行环境
 */
final class ConsoleApplication extends Application
{
    private static ?ConsoleApplication $obj = null;

    public function __construct(string $name = 'zhamao-framework')
    {
        if (self::$obj !== null) {
            throw new SingletonViolationException(self::class);
        }

        // 初始化 Composer 变量
        if (file_exists(WORKING_DIR . '/runtime/composer.phar')) {
            echo '* Using native composer' . PHP_EOL;
            putenv('COMPOSER_EXECUTABLE=' . WORKING_DIR . '/runtime/composer.phar');
        }

        $this->registerCommandLoader();

        // 执行父级初始化
        parent::__construct($name, ZM_VERSION);

        $this->registerGlobalOptions();

        // 设置单例，阻止后续实例化
        self::$obj = $this;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::doRun($input, $output);
        } catch (\Throwable $e) {
            // 输出错误信息
            echo zm_internal_errcode('E00005') . "{$e->getMessage()} at {$e->getFile()}({$e->getLine()})" . PHP_EOL;
            return 1;
        }
    }

    /**
     * 注册全局选项，应用到所有命令
     */
    public function registerGlobalOptions(): void
    {
        $this->getDefinition()->addOptions([
            new InputOption('debug', 'd', InputOption::VALUE_NONE, '启用调试模式'),
            new InputOption('env', 'e', InputOption::VALUE_REQUIRED, '指定运行环境', 'development'),
            new InputOption('config-dir', 'c', InputOption::VALUE_REQUIRED, '指定配置文件目录', SOURCE_ROOT_DIR . '/config'),
            new InputOption('log-level', 'l', InputOption::VALUE_REQUIRED, '指定日志等级', 'info'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        // 初始化内核
        $framework = Framework::getInstance();
        $framework->runtime_preferences = $framework->runtime_preferences
            ->withConfigDir($input->getOption('config-dir'))
            ->withEnvironment($input->getOption('env'))
            ->enableDebugMode($input->getOption('debug'))
            ->withLogLevel($input->getOption('log-level'));
        $framework->bootstrap();
        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * 注册命令加载器
     */
    private function registerCommandLoader(): void
    {
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
        // TODO: 加载插件命令，可以考虑自定义 CommandLoader
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
    }
}
