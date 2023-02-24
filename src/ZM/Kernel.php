<?php

declare(strict_types=1);

namespace ZM;

use OneBot\Util\Singleton;
use ZM\Bootstrap\Bootstrapper;

class Kernel
{
    use Singleton;

    private array $bootstrappers = [];

    private string $config_dir;

    private string $environment;

    private bool $debug_mode = false;

    private string $log_level;

    private bool $test_mode = false;

    private function __construct()
    {
        $this->registerBootstrappers([
            Bootstrap\LoadConfiguration::class,         // 加载配置文件
            Bootstrap\LoadGlobalDefines::class,         // 加载框架级别的全局常量声明
            Bootstrap\RegisterLogger::class,            // 加载 Logger
            Bootstrap\HandleExceptions::class,          // 注册异常处理器
            Bootstrap\RegisterEventProvider::class,     // 绑定框架的 EventProvider 到 libob 的 Driver 上
            Bootstrap\SetInternalTimezone::class,       // 设置时区
        ]);
    }

    /**
     * 获取版本号
     */
    public function version(): string
    {
        return ZM_VERSION;
    }

    /**
     * 获取配置文件目录
     */
    public function getConfigDir(): string
    {
        return $this->config_dir;
    }

    /**
     * 设置配置文件目录
     */
    public function setConfigDir(string $config_dir): void
    {
        $this->config_dir = $config_dir;
    }

    /**
     * 获取或检查运行环境
     *
     * @param array|string ...$environments
     */
    public function environment(...$environments): string|bool
    {
        if (empty($environments)) {
            return $this->environment;
        }

        return in_array($this->environment, $environments, true);
    }

    /**
     * 设置运行环境
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    public function isDebugMode(): bool
    {
        return $this->debug_mode;
    }

    public function setDebugMode(bool $debug_mode): void
    {
        $this->debug_mode = $debug_mode;
    }

    public function getLogLevel(): string
    {
        return $this->isDebugMode() ? 'debug' : $this->log_level;
    }

    public function setLogLevel(string $log_level): void
    {
        $this->log_level = $log_level;
    }

    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public function runningUnitTests(): bool
    {
        return $this->test_mode;
    }

    public function setTestMode(bool $test_mode): void
    {
        $this->test_mode = $test_mode;
    }

    public function registerBootstrappers(array $bootstrappers): void
    {
        $this->bootstrappers = array_merge($this->bootstrappers, $bootstrappers);
    }

    public function bootstrap(): void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            /* @var Bootstrapper $bootstrapper */
            (new $bootstrapper())->bootstrap($this);
        }
    }
}
