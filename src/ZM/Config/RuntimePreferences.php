<?php

declare(strict_types=1);

namespace ZM\Config;

class RuntimePreferences
{
    protected string $environment = 'development';

    protected bool $debug_mode = false;

    protected string $log_level = 'info';

    protected string $config_dir = SOURCE_ROOT_DIR . '/config';

    public function environment(...$environments): string|bool
    {
        if (empty($environments)) {
            return $this->environment;
        }

        return in_array($this->environment, $environments, true);
    }

    public function withEnvironment(string $environment): self
    {
        $copy = clone $this;
        $copy->environment = $environment;
        return $copy;
    }

    public function isDebugMode(): bool
    {
        return $this->debug_mode;
    }

    public function enableDebugMode(bool $debug_mode): self
    {
        $copy = clone $this;
        $copy->debug_mode = $debug_mode;
        return $copy;
    }

    public function getLogLevel(): string
    {
        return $this->isDebugMode() ? 'debug' : $this->log_level;
    }

    public function withLogLevel(string $log_level): self
    {
        $copy = clone $this;
        $copy->log_level = $log_level;
        return $copy;
    }

    public function getConfigDir(): string
    {
        // fallback to internal config dir if config_dir not exists
        return is_dir($this->config_dir) ? $this->config_dir : FRAMEWORK_ROOT_DIR . '/config';
    }

    public function withConfigDir(string $config_dir): self
    {
        $copy = clone $this;
        $copy->config_dir = $config_dir;
        return $copy;
    }

    public function runningInInteractiveTerminal(): bool
    {
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDIN) && posix_isatty(STDOUT);
        }

        // fallback to stream_isatty() if posix_isatty() is not available (e.g. on Windows)
        return function_exists('stream_isatty') && stream_isatty(STDIN) && stream_isatty(STDOUT);
    }

    public function runningUnitTests(): bool
    {
        return defined('PHPUNIT_RUNNING') && constant('PHPUNIT_RUNNING');
    }
}
