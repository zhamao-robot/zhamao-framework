<?php

declare(strict_types=1);

namespace ZM\Plugin;

use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\BindEvent;
use ZM\Annotation\OneBot\BotEvent;
use ZM\Exception\PluginException;
use ZM\Store\FileSystem;

class PluginManager
{
    private const DEFAULT_META = [
        'name' => '<anonymous>',
        'version' => 'dev',
        'dir' => '',
        'object' => null,
        'entry_file' => null,
        'autoload' => null,
        'dependencies' => [],
    ];

    /** @var array|string[] 缺省的自动加载插件的入口文件 */
    public static array $default_entries = [
        'main.php',
        'entry.php',
        'index.php',
    ];

    /** @var array 插件信息列表 */
    private static array $plugins = [];

    /**
     * 传入插件父目录，扫描插件目录下的所有插件并注册添加
     *
     * @param  string          $dir 插件目录
     * @return int             返回添加插件的数量
     * @throws PluginException
     */
    public static function addPluginsFromDir(string $dir): int
    {
        // 遍历插件目录
        $list = FileSystem::scanDirFiles($dir, false, false, true);
        $cnt = 0;
        foreach ($list as $item) {
            // 必须是目录形式的插件
            if (!is_dir($item)) {
                continue;
            }
            $plugin_meta = self::DEFAULT_META;
            $plugin_meta['dir'] = $item;

            // 看看有没有插件信息文件
            $info_file = $item . '/zmplugin.json';
            $main_file = '';
            // 如果有的话，就从插件信息文件中找到插件信息
            if (is_file($info_file)) {
                $info = json_decode(file_get_contents($info_file), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    logger()->error('插件信息文件解析失败: ' . json_last_error_msg());
                    continue;
                }
                // 设置名称（如果有）
                $plugin_meta['name'] = $info['name'] ?? ('<anonymous:' . pathinfo($item, PATHINFO_BASENAME) . '>');
                // 设置版本（如果有）
                if (isset($info['version'])) {
                    $plugin_meta['version'] = $info['version'];
                }
                // 设置了入口文件，则遵循这个入口文件
                if (isset($info['main'])) {
                    $main_file = FileSystem::isRelativePath($info['main']) ? ($item . '/' . $info['main']) : $info['main'];
                } else {
                    $main_file = self::matchDefaultEntry($item);
                }

                // 检查有没有 composer.json 和 vendor/autoload.php 自动加载，如果有的话，那就写上去
                $composer_file = $item . '/composer.json';
                if (is_file(zm_dir($composer_file))) {
                    // composer.json 存在，那么就加载这个插件
                    $composer = json_decode(file_get_contents($composer_file), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        logger()->error('插件 composer.json 文件解析失败: ' . json_last_error_msg());
                        continue;
                    }
                    if (isset($composer['autoload']['psr-4']) && is_assoc_array($composer['autoload']['psr-4'])) {
                        $plugin_meta['autoload'] = $composer['autoload']['psr-4'];
                    }
                }

                // 主文件存在，则加载
                if (is_file(zm_dir($main_file))) {
                    // 如果入口文件存在，那么就加载这个插件
                    $plugin_meta['entry_file'] = $main_file;
                }

                // composer.json 不存在，那么就忽略这个插件，并报 warning
                if (!is_file(zm_dir($composer_file)) && $plugin_meta['entry_file'] === null) {
                    logger()->warning('插件 ' . $item . ' 不存在入口文件，也没有自动加载文件和内建 Composer，跳过加载');
                    continue;
                }
            } else {
                $plugin_meta['name'] = '<unnamed:' . pathinfo($item, PATHINFO_BASENAME) . '>';
                // 到这里，说明没有 zmplugin.json 这个文件，那么我们就直接匹配
                $main_file = self::matchDefaultEntry($item);
                if (is_file(zm_dir($main_file))) {
                    // 如果入口文件存在，那么就加载这个插件
                    $plugin_meta['entry_file'] = $main_file;
                } else {
                    continue;
                }
            }

            // 到这里，说明插件信息收集齐了，只需要加载就行了
            self::addPlugin($plugin_meta);
            ++$cnt;
        }
        return $cnt;
    }

    /**
     * 添加插件到全局注册中
     *
     * @throws PluginException
     */
    public static function addPlugin(array $meta = []): void
    {
        if (!isset($meta['name'])) {
            throw new PluginException('Plugin must have a name!');
        }
        logger()->debug('Adding plugin: ' . $meta['name']);

        self::$plugins[$meta['name']] = $meta;

        // 存在直接声明的对象，那么直接初始化
        if (isset($meta['object']) && $meta['object'] instanceof ZMPlugin) {
            return;
        }

        // 存在入口文件（单文件），从单文件加载
        if (isset($meta['entry_file']) && is_file(zm_dir($meta['entry_file']))) {
            $zmplugin = self::$plugins[$meta['name']]['object'] = require $meta['entry_file'];
            if (!$zmplugin instanceof ZMPlugin) {
                unset(self::$plugins[$meta['name']]);
                throw new PluginException('插件 ' . $meta['name'] . ' 的入口文件 ' . $meta['entry_file'] . ' 必须返回一个 ZMPlugin 对象');
            }
            return;
        }

        // 存在自动加载，检测 vendor/autoload.php 是否存在，如果存在，那么就加载
        if (isset($meta['autoload'], $meta['dir']) && $meta['dir'] !== '' && is_file($meta['dir'] . '/vendor/autoload.php')) {
            require_once $meta['dir'] . '/vendor/autoload.php';
            return;
        }
        // 如果都不存在，那是不可能的事情，抛出一个谁都没见过的异常
        unset(self::$plugins[$meta['name']]);
        throw new PluginException('插件 ' . $meta['name'] . ' 无法加载，因为没有入口文件，也没有自动加载文件和内建 Composer');
    }

    public static function enablePlugins(AnnotationParser $parser): void
    {
        foreach (self::$plugins as $name => $plugin) {
            if (!isset($plugin['internal'])) {
                logger()->info('Enabling plugin: ' . $name);
            }
            if (isset($plugin['object']) && $plugin['object'] instanceof ZMPlugin) {
                $obj = $plugin['object'];
                // 将 Event 加入事件监听
                foreach ($obj->getEvents() as $event) {
                    $bind = new BindEvent($event[0], $event[2]);
                    $bind->on($event[1]);
                    AnnotationMap::$_list[BindEvent::class][] = $bind;
                }
                // 将 Routes 加入事件监听
                foreach ($obj->getRoutes() as $route) {
                    $parser->parseSpecial($route);
                }
                // 将 BotEvents 加入事件监听
                foreach ($obj->getBotEvents() as $event) {
                    AnnotationMap::$_list[BotEvent::class][] = $event;
                }
                // 将 BotCommand 加入事件监听
                foreach ($obj->getBotCommands() as $cmd) {
                    $parser->parseSpecial($cmd);
                }
            } elseif (isset($plugin['autoload'], $plugin['dir'])) {
                foreach ($plugin['autoload'] as $k => $v) {
                    $parser->addRegisterPath($plugin['dir'] . '/' . $v . '/', trim($k, '\\'));
                }
            }
        }
    }

    private static function matchDefaultEntry(string $dir): string
    {
        $main = '';
        // 没有设置入口文件，则遍历默认入口文件列表
        foreach (self::$default_entries as $entry) {
            $main_file = $dir . '/' . $entry;
            if (is_file(zm_dir($main_file))) {
                $main = $main_file;
                break;
            }
        }
        return $main;
    }
}
