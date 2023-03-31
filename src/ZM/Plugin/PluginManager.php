<?php

declare(strict_types=1);

namespace ZM\Plugin;

use Jelix\Version\VersionComparator;
use ZM\Annotation\AnnotationMap;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\BindEvent;
use ZM\Command\Command;
use ZM\Exception\FileSystemException;
use ZM\Exception\PluginException;
use ZM\Store\FileSystem;
use ZM\Store\PharHelper;

class PluginManager
{
    /** @var array<string, PluginMeta> 插件信息列表 */
    private static array $plugins = [];

    public static function getPlugins(): array
    {
        return self::$plugins;
    }

    /**
     * 传入插件父目录，扫描插件目录下的所有插件并注册添加（开发插件）
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

            // 先看有没有 composer.json，没有则不是正常的插件，发个 notice 然后跳过
            $meta_file = $item . '/composer.json';
            if (!is_file($meta_file)) {
                logger()->notice('插件目录 {dir} 没有插件元信息（composer.json），跳过扫描。', ['dir' => $item]);
                continue;
            }

            // 检验元信息是否合法，不合法发个 notice 然后跳过
            $json_meta = json_decode(file_get_contents($meta_file), true);
            if (!is_array($json_meta)) {
                logger()->notice('插件目录 {dir} 的插件元信息（composer.json）不是有效的 JSON，跳过扫描。', ['dir' => $item]);
                continue;
            }
            if (!isset($json_meta['extra']['zm-plugin-version'], $json_meta['name'])) {
                logger()->notice('插件目录 {dir} 的插件元信息未提供版本和名称，不是有效的插件，跳过扫描。', ['dir' => $item]);
                continue;
            }

            // 构造一个元信息对象
            $meta = new PluginMeta(
                name: $json_meta['name'],
                version: $json_meta['extra']['zm-plugin-version'],
                description: $json_meta['description'] ?? '',
                plugin_type: ZM_PLUGIN_TYPE_SOURCE,
                root_dir: $item
            );
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
            $plugin_file_path = zm_dir('phar://' . $phar_path . '/composer.json');
            if (!file_exists($plugin_file_path)) {
                throw new PluginException('插件元信息 composer.json 文件不存在');
            }
            // 解析元信息的 JSON
            $json_meta = json_decode(file_get_contents($plugin_file_path), true);
            // 失败抛出异常
            if (!is_array($json_meta)) {
                throw new PluginException('插件信息文件解析失败');
            }
            // 解析 name 和版本失败
            if (!isset($json_meta['extra']['zm-plugin-version'], $json_meta['name'])) {
                throw new PluginException('插件文件 ' . $phar_path . ' 的插件元信息未提供版本和名称，不是有效的插件');
            }
            // $phar 这时应该是一个 ZMPlugin 对象，写入元信息
            // 构造一个元信息对象
            $meta = new PluginMeta(
                name: $json_meta['name'],
                version: $json_meta['extra']['zm-plugin-version'],
                description: $json_meta['description'] ?? '',
                plugin_type: ZM_PLUGIN_TYPE_PHAR,
                root_dir: zm_dir('phar://' . $phar_path)
            );
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
        $try_list = [
            SOURCE_ROOT_DIR . '/vendor',
            WORKING_DIR . '/vendor',
        ];
        foreach ($try_list as $v) {
            if (file_exists($v . '/composer/installed.json')) {
                $vendor_dir = $v;
                break;
            }
        }
        if (!isset($vendor_dir)) {
            logger()->notice('找不到 Composer 的 installed.json 文件，跳过扫描 Composer 插件');
            return 0;
        }
        $json = json_decode(file_get_contents($vendor_dir . '/composer/installed.json'), true);
        if (!is_array($json)) {
            logger()->notice('Composer 的 installed.json 文件解析失败，跳过扫描 Composer 插件');
            return 0;
        }
        $cnt = 0;
        foreach ($json['packages'] as $item) {
            $root_dir = $vendor_dir . '/' . $item['name'];
            $meta_file = zm_dir($root_dir . '/composer.json');
            if (!file_exists($meta_file)) {
                continue;
            }

            // 检验元信息是否合法，不合法发个 notice 然后跳过
            $json_meta = json_decode(file_get_contents($meta_file), true);
            if (!is_array($json_meta)) {
                logger()->notice('插件目录 {dir} 的插件元信息（composer.json）不是有效的 JSON，跳过扫描。', ['dir' => $item]);
                continue;
            }
            // 解析 name 和版本失败
            if (!isset($json_meta['extra']['zm-plugin-version'], $json_meta['name'])) {
                continue;
            }

            // 构造一个元信息对象
            $meta = new PluginMeta(
                name: $json_meta['name'],
                version: $json_meta['extra']['zm-plugin-version'],
                description: $json_meta['description'] ?? '',
                plugin_type: ZM_PLUGIN_TYPE_COMPOSER,
                root_dir: $root_dir
            );
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
        logger()->debug('Adding plugin: ' . $meta->getName() . '(type:' . $meta->getPluginType() . ')');
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
            throw new PluginException('插件 ' . $meta->getName() . ' 已经存在（类型为' . self::$plugins[$meta->getName()]->getPluginType() . '），无法加载同名插件或重复加载！');
        }
        self::$plugins[$meta->getName()] = $meta;
    }

    /**
     * 启用所有插件
     *
     * @param  AnnotationParser $parser 传入注解解析器，用于将插件中的事件注解解析出来
     * @throws PluginException
     */
    public static function enablePlugins(AnnotationParser $parser, array $disable_list = []): void
    {
        foreach (self::$plugins as $name => $meta) {
            if (in_array($name, $disable_list)) {
                $meta->disablePlugin();
            }
            if (!$meta->isEnabled()) {
                logger()->notice('插件 ' . $name . ' 已被禁用');
                continue;
            }
            // 除了内建插件外，输出 log 告知启动插件
            if ($meta->getPluginType() !== ZM_PLUGIN_TYPE_NATIVE) {
                logger()->info('正在启用插件 ' . $name);
            }
            /* 插件从 zmplugin.json 改为 composer 了，所以不需要自己判断依赖
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
            }*/
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
                // 设置 TimerTick 注解
                foreach ($entity->getTimerTicks() as $tick) {
                    AnnotationMap::addSingleAnnotation($tick);
                    $parser->parseSpecial($tick);
                }
            }
            // 如果设置了 Autoload file，那么将会把 psr-4 的加载路径丢进 parser
            foreach ($meta->getAutoloadPsr4() as $namespace => $path) {
                $parser->addPsr4Path($meta->getRootDir() . '/' . $path . '/', trim($namespace, '\\'), [
                    'plugin:' . $name,
                ]);
            }
        }
    }

    /**
     * 打包插件到 Phar
     *
     * @throws PluginException
     * @throws FileSystemException
     */
    public static function packPlugin(string $plugin_name, string $build_dir, ?Command $command_context = null): string
    {
        // 必须是源码模式才行
        if (!isset(self::$plugins[$plugin_name]) || self::$plugins[$plugin_name]->getPluginType() !== ZM_PLUGIN_TYPE_SOURCE) {
            throw new PluginException("没有找到名字为 {$plugin_name} 的插件（要打包的插件必须是源码模式）。");
        }
        try {
            // 创建目录
            FileSystem::createDir($build_dir);
            $plugin = self::$plugins[$plugin_name];
            // 插件目录
            $dir = $plugin->getRootDir();
            // 先判断是不是可写的
            PharHelper::ensurePharWritable();
            // 拼接 phar 名称，通过插件名和版本号（如果没有版本号则使用 1.0-dev 作为版本号）
            $phar_name = $plugin->getName() . '_' . $plugin->getVersion() . '.phar';
            $phar_name = zm_dir($build_dir . '/' . $phar_name);
            // 判断文件如果存在的话是否是可写的
            FileSystem::ensureFileWritable($phar_name);
            // 文件存在先删除
            if (file_exists($phar_name)) {
                $command_context?->info('Phar 文件 ' . $phar_name . ' 已存在，删除中...');
                unlink($phar_name);
            }
            // 先执行一些打包前检查的 bootstrap
            // 1. 检查插件是否引用了 require-dev 的内容（检查 --no-dev）
            if (file_exists($dir . '/composer.json') && file_exists($dir . '/vendor/composer/installed.json')) {
                $json = json_decode(file_get_contents($dir . '/vendor/composer/installed.json'), true);
                if (!isset($json['dev'])) {
                    $command_context?->error('插件的 Composer 未正确配置，忽略检查 dev 模式！');
                } elseif ($json['dev'] === true) {
                    throw new PluginException(
                        "插件的 Composer 配置了 dev 模式，但是打包时没有使用 --no-dev 选项，无法打包\n" .
                        '请先进入插件目录，执行 composer update --no-dev'
                    );
                }
            }
            // 创建 Phar 对象
            $phar = new \Phar($phar_name, 0);
            // 调用插件的打包的用户自定义前置方法
            $plugin->getEntity()?->emitPack();
            // 扫描插件目录
            $dir_list = FileSystem::scanDirFiles($dir, true, true);
            if ($command_context instanceof Command) {
                $dir_list = $command_context->progress()->iterate($dir_list);
            }
            $file_added = 0;
            $file_ignored = 0;
            foreach ($dir_list as $v) {
                // 过滤文件
                if ($plugin->getEntity()?->emitFilterPack($v) === false) {
                    ++$file_ignored;
                    continue;
                }
                // 添加文件
                $phar->addFromString($v, php_strip_whitespace(zm_dir($dir . '/' . $v)));
                ++$file_added;
            }
            // 找有没有 main，没有 main 就不添加 stub
            $main = (json_decode(file_get_contents($dir . '/composer.json'), true)['extra']['zm-plugin-main'] ?? 'main.php');
            if (file_exists(zm_dir($dir . '/' . $main)) && $phar->offsetExists($main)) {
                $command_context?->info('设置插件默认入口文件 ' . $main);
                $phar->setStub($phar->setDefaultStub($main));
            } else {
                $phar->setStub('<?php __HALT_COMPILER();');
            }
            // 停止
            $phar->stopBuffering();
            // 输出结果
            $command_context?->info("共添加 {$file_added} 个文件" . ($file_ignored > 0 ? "，忽略 {$file_ignored} 个文件" : ''));
            return $phar_name;
        } catch (\PharException $e) {
            throw new PluginException("插件 {$plugin_name} 打包失败，原因为 Phar 异常：\n" . $e->getMessage(), previous: $e);
        }
    }

    /**
     * 检查插件是否被加载
     *
     * @param string $name 插件名称
     */
    public static function isPluginExists(string $name): bool
    {
        return isset(self::$plugins[$name]);
    }
}
