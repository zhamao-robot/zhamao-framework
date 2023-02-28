<?php

declare(strict_types=1);

namespace ZM\Plugin\Strategy;

use Psr\Log\LoggerInterface;
use ZM\Plugin\PluginManager;

abstract class PluginInstallStrategy
{
    protected string $error = '';

    protected string $installed_name = '';

    public function __construct(
        protected string $input,
        protected string $plugin_dir,
        protected string $root_composer_path = '',
        protected ?LoggerInterface $logger = null,
    ) {
        if ($this->root_composer_path === '') {
            $this->root_composer_path = zm_dir(WORKING_DIR);
        }
        if ($this->logger === null) {
            $this->logger = ob_logger();
        }
    }

    abstract public function install(array $option = []): bool;

    public function getError(): string
    {
        return $this->error;
    }

    public function getInstalledName(): string
    {
        return $this->installed_name;
    }

    /**
     * 用于检查 Composer 文件的信息是否完整
     */
    protected function checkComposerIntegrity(mixed $composer): bool
    {
        // 必须是 array
        if (!is_array($composer)) {
            $this->error = 'composer.json 元信息获取出错';
            return false;
        }
        if (!isset($composer['extra']['zm-plugin-version'])) {
            $this->error = 'composer.json 内没有标明该炸毛插件的版本，或该仓库不是炸毛插件';
            return false;
        }
        if (!isset($composer['name'])) {
            $this->error = 'composer.json 插件元信息内没有名字！';
            return false;
        }
        $plugin_name = $composer['name'];
        if (PluginManager::isPluginExists($plugin_name)) {
            $this->error = "插件 {$plugin_name} 已存在，无法再次安装";
            return false;
        }
        return true;
    }
}
