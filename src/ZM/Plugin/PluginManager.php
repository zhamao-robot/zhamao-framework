<?php

declare(strict_types=1);

namespace ZM\Plugin;

use ZM\Exception\PluginException;

class PluginManager
{
    /** @var array 插件信息列表 */
    private static array $plugins = [];

    /**
     * @throws PluginException
     */
    public static function addPlugin(array $meta = []): void
    {
        // 首先检测 meta 是否存在 plugin 对象
        if (isset($meta['plugin'])) {
            // 存在的话，说明是单例插件，调用对象内的方法注册事件就行了
            $meta['type'] = 'instant';
            self::$plugins[$meta['name']] = $meta;
            return;
        }
        if (isset($meta['dir'])) {
            // 不存在的话，说明是多文件插件，是设置了 zmplugin.json 的目录，此目录为自动加载的
            $meta['type'] = 'dir';
            self::$plugins[$meta['name']] = $meta;
            return;
        }
        // 两者都不存在的话，说明是错误的插件
        throw new PluginException('plugin meta must have plugin or dir');
    }

    public static function getPlugins(): array
    {
        return self::$plugins;
    }
}
