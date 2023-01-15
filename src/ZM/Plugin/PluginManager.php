<?php

declare(strict_types=1);

namespace ZM\Plugin;

use Jelix\Version\VersionComparator;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\BindEvent;
use ZM\Exception\PluginException;
use ZM\Store\FileSystem;

class PluginManager
{
    /** @var array<string, PluginMeta> 插件信息列表 */
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
        if (!is_dir($dir)) {
            return 0;
        }
        $list = FileSystem::scanDirFiles($dir, false, false, true);
        $cnt = 0;
        foreach ($list as $item) {
            // 检查是不是 phar 格式的插件
            if (is_file($item) && pathinfo($item, PATHINFO_EXTENSION) === 'phar') {
                // 如果是PHP文件，尝试添加插件
                self::addPluginFromPhar($item);
                ++$cnt;
                continue;
            }

            // 必须是目录形式的插件
            if (!is_dir($item)) {
                continue;
            }

            // 先看有没有 zmplugin.json，没有则不是正常的插件，发个 notice 然后跳过
            $meta_file = $item . '/zmplugin.json';
            if (!is_file($meta_file)) {
                logger()->notice('插件目录 {dir} 没有插件元信息（zmplugin.json），跳过扫描。', ['dir' => $item]);
                continue;
            }

            // 检验元信息是否合法，不合法发个 notice 然后跳过
            $json_meta = json_decode(file_get_contents($meta_file), true);
            if (!is_array($json_meta)) {
                logger()->notice('插件目录 {dir} 的插件元信息（zmplugin.json）不是有效的 JSON，跳过扫描。', ['dir' => $item]);
                continue;
            }

            // 构造一个元信息对象
            $meta = new PluginMeta($json_meta, ZM_PLUGIN_TYPE_SOURCE, $item);
            if ($meta->getEntryFile() === null && $meta->getAutoloadFile() === null) {
                logger()->notice('插件 ' . $item . ' 不存在入口文件，也没有自动加载文件和内建 Composer，跳过加载');
                continue;
            }

            // 添加插件到全局列表
            self::addPlugin($meta);
            ++$cnt;
        }
        return $cnt;
    }

    /**
     * 添加一个 Phar 文件形式的插件
     *
     * @throws PluginException
     */
    public static function addPluginFromPhar(string $phar_path): void
    {
        $meta = [];
        try {
            // 加载这个 Phar 文件
            $phar = require $phar_path;
            // 读取元信息
            $plugin_file_path = zm_dir('phar://' . $phar_path . '/zmplugin.json');
            if (!file_exists($plugin_file_path)) {
                throw new PluginException('插件元信息 zmplugin.json 文件不存在');
            }
            // 解析元信息的 JSON
            $meta_json = json_decode(file_get_contents($plugin_file_path), true);
            // 失败抛出异常
            if (!is_array($meta_json)) {
                throw new PluginException('插件信息文件解析失败');
            }
            // $phar 这时应该是一个 ZMPlugin 对象，写入元信息
            $meta = new PluginMeta($meta_json, ZM_PLUGIN_TYPE_PHAR, zm_dir('phar://' . $phar_path));
            // 如果已经返回了一个插件对象，那么直接塞进去实体
            if ($phar instanceof ZMPlugin) {
                $meta->bindEntity($phar);
            }
            // 添加到插件列表
            self::addPlugin($meta);
        } catch (\Throwable $e) {
            throw new PluginException('Phar 插件 ' . $phar_path . ' 加载失败: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * 从 Composer 添加插件
     * @throws PluginException
     */
    public static function addPluginsFromComposer(): int
    {
        $installed_file = SOURCE_ROOT_DIR . '/vendor/composer/installed.json';
        if (!file_exists($installed_file)) {
            logger()->notice('找不到 Composer 的 installed.json 文件，跳过扫描 Composer 插件');
            return 0;
        }
        $json = json_decode(file_get_contents($installed_file), true);
        if (!is_array($json)) {
            logger()->notice('Composer 的 installed.json 文件解析失败，跳过扫描 Composer 插件');
            return 0;
        }
        $cnt = 0;
        foreach ($json['packages'] as $item) {
            $root_dir = SOURCE_ROOT_DIR . '/vendor/' . $item['name'];
            $meta_file = zm_dir($root_dir . '/zmplugin.json');
            if (!file_exists($meta_file)) {
                continue;
            }

            // 检验元信息是否合法，不合法发个 notice 然后跳过
            $json_meta = json_decode(file_get_contents($meta_file), true);
            if (!is_array($json_meta)) {
                logger()->notice('插件目录 {dir} 的插件元信息（zmplugin.json）不是有效的 JSON，跳过扫描。', ['dir' => $item]);
                continue;
            }

            // 构造一个元信息对象
            $meta = new PluginMeta($json_meta, ZM_PLUGIN_TYPE_COMPOSER, zm_dir($root_dir));
            if ($meta->getEntryFile() === null && $meta->getAutoloadFile() === null) {
                logger()->notice('插件 ' . $item . ' 不存在入口文件，也没有自动加载文件和内建 Composer，跳过加载');
                continue;
            }

            // 添加插件到全局列表
            self::addPlugin($meta);
            ++$cnt;
        }
        return $cnt;
    }

    /**
     * 根据插件元信息对象添加一个插件到框架的全局插件库中
     *
     * @throws PluginException
     */
    public static function addPlugin(PluginMeta $meta): void
    {
        logger()->debug('Adding plugin: ' . $meta->getName());
        // 首先看看有没有 entity，如果还没有 entity，且 entry_file 有东西，那么就从 entry_file 获取 ZMPlugin 对象
        if ($meta->getEntity() === null) {
            if (($entry_file = $meta->getEntryFile()) !== null) {
                $entity = require $entry_file;
                if ($entity instanceof ZMPlugin) {
                    $meta->bindEntity($entity);
                }
            }
        }
        // 如果设置了 ZMPlugin entity，并且已设置了 PluginLoad 事件，那就回调
        // 接下来看看有没有 autoload，有的话 require_once 一下
        if (($autoload = $meta->getAutoloadFile()) !== null) {
            require_once $autoload;
        }
        // 如果既没有 entity，也没有 autoload，那就要抛出异常了
        if ($meta->getEntity() === null && $meta->getAutoloadFile() === null) {
            throw new PluginException('插件 ' . $meta->getName() . ' 既没有入口文件，也没有自动加载文件，无法加载');
        }
        // 检查同名插件，如果有同名插件，则抛出异常
        if (isset(self::$plugins[$meta->getName()])) {
            throw new PluginException('插件 ' . $meta->getName() . ' 已经存在，无法加载同名插件或重复加载！');
        }
        self::$plugins[$meta->getName()] = $meta;
    }

    /**
     * 启用所有插件
     *
     * @param  AnnotationParser $parser 传入注解解析器，用于将插件中的事件注解解析出来
     * @throws PluginException
     */
    public static function enablePlugins(AnnotationParser $parser): void
    {
        foreach (self::$plugins as $name => $meta) {
            // 除了内建插件外，输出 log 告知启动插件
            if ($meta->getPluginType() !== ZM_PLUGIN_TYPE_NATIVE) {
                logger()->info('Enabling plugin: ' . $name);
            }
            // 先判断依赖关系，如果声明了依赖，但依赖不合规则报错崩溃
            foreach ($meta->getDependencies() as $dep_name => $dep_version) {
                // 缺少依赖的插件，不行
                if (!isset(self::$plugins[$dep_name])) {
                    throw new PluginException('插件 ' . $name . ' 依赖插件 ' . $dep_name . '，但是没有找到这个插件');
                }
                // 依赖的插件版本不对，不行
                if (VersionComparator::compareVersionRange(self::$plugins[$dep_name]->getVersion(), $dep_version) === false) {
                    throw new PluginException('插件 ' . $name . ' 依赖插件 ' . $dep_name . '，但是这个插件的版本不符合要求');
                }
            }
            // 如果插件为单文件形式，且设置了 pluginLoad 事件，那就调用
            $meta->getEntity()?->emitPluginLoad($parser);
            if (($entity = $meta->getEntity()) instanceof ZMPlugin) {
                // 将 BotAction 加入事件监听
                foreach ($entity->getBotActions() as $action) {
                    AnnotationMap::addSingleAnnotation($action);
                    $parser->parseSpecial($action);
                }
                // 将 BotCommand 加入事件监听
                foreach ($entity->getBotCommands() as $cmd) {
                    AnnotationMap::addSingleAnnotation($cmd);
                    $parser->parseSpecial($cmd);
                }
                // 将 Event 加入事件监听
                foreach ($entity->getEvents() as $event) {
                    $bind = new BindEvent($event[0], $event[2]);
                    $bind->on($event[1]);
                    AnnotationMap::addSingleAnnotation($bind);
                }
                // 将 Routes 加入事件监听
                foreach ($entity->getRoutes() as $route) {
                    $parser->parseSpecial($route);
                }
                // 将 BotEvents 加入事件监听
                foreach ($entity->getBotEvents() as $event) {
                    AnnotationMap::addSingleAnnotation($event);
                }
                // 将 Cron 加入注解
                foreach ($entity->getCrons() as $cron) {
                    AnnotationMap::addSingleAnnotation($cron);
                    $parser->parseSpecial($cron);
                }
                // 设置 @Init 注解
                foreach ($entity->getInits() as $init) {
                    AnnotationMap::addSingleAnnotation($init);
                }
            }
            // 如果设置了 Autoload file，那么将会把 psr-4 的加载路径丢进 parser
            foreach ($meta->getAutoloadPsr4() as $namespace => $path) {
                $parser->addPsr4Path($meta->getRootDir() . '/' . $path . '/', trim($namespace, '\\'));
            }
        }
    }

    /**
     * 打包插件到 Phar
     *
     * @throws PluginException
     */
    public static function packPlugin(string $name): string
    {
        // 先遍历下插件目录下是否有这个插件，没有这个插件则不能打包
        $plugin_dir = config('global.plugin.load_dir', SOURCE_ROOT_DIR . '/plugins');
        // 模拟加载一遍插件
        self::addPluginsFromDir($plugin_dir);

        // 必须是源码模式才行
        if (!isset(self::$plugins[$name]) || self::$plugins[$name]->getPluginType() !== ZM_PLUGIN_TYPE_SOURCE) {
            throw new PluginException("没有找到名字为 {$name} 的插件（要打包的插件必须是源码模式）。");
        }

        $plugin = self::$plugins[$name];
        // 插件目录
        $dir = $plugin->getRootDir();
        // TODO: 写到这了
        // 插件加载方式判断
        return '';
    }
}
