<?php

declare(strict_types=1);

namespace ZM\Plugin\Strategy;

use ZM\Utils\ZMUtil;

class ComposerStrategy extends PluginInstallStrategy
{
    public function install(array $option = []): bool
    {
        $plugin_name = trim($this->input);
        $env = ZMUtil::getComposerExecutable();
        passthru("{$env} require {$plugin_name}", $code);
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, SIG_IGN);
        }
        if ($code !== 0) {
            $this->error = '使用 composer 仓库引入插件出现了一些错误，请看上方错误。';
            return false;
        }
        $composer = ZMUtil::getComposerMetadata(WORKING_DIR . '/vendor/' . $plugin_name . '/');
        if ($composer === null) {
            passthru("{$env} remove {$plugin_name}", $code);
            if ($code !== 0) {
                $this->error = 'Composer 插件的 composer.json 解析失败，且卸载插件失败';
                return false;
            }
            $this->error = 'Composer 插件的 composer.json 解析失败';
            return false;
        }
        if ($composer['name'] !== $plugin_name) {
            passthru("{$env} remove {$plugin_name}", $code);
            if ($code !== 0) {
                $this->error = 'Composer 插件的名称与用户输入的插件名称元信息不符，可能是内部错误，且卸载插件失败';
                return false;
            }
            $this->error = 'Composer 插件的名称与用户输入的插件名称元信息不符，可能是内部错误';
            return false;
        }
        if (!isset($composer['extra']['zm-plugin-version'])) {
            // 回退
            passthru("{$env} remove {$plugin_name}", $code);
            if ($code !== 0) {
                $this->error = '该 Composer 插件不是炸毛框架插件，且卸载插件失败';
                return false;
            }
            $this->error = '该 Composer 插件不是炸毛框架插件！';
            return false;
        }
        // composer 安装的插件理论上和用户输入的是同名的
        $this->installed_name = $plugin_name;
        return true;
    }
}
