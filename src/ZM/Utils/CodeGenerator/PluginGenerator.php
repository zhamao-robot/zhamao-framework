<?php

declare(strict_types=1);

namespace ZM\Utils\CodeGenerator;

use ZM\Store\FileSystem;

/**
 * Class PluginGenerator
 * 插件脚手架生成器
 */
class PluginGenerator
{
    public function __construct(private string $name, private string $plugin_dir)
    {
    }

    /**
     * 开始生成
     *
     * @param array $options 传入的命令行选项
     */
    public function generate(array $options): void
    {
        // 先检查插件目录是否存在，不存在则创建
        FileSystem::createDir($this->plugin_dir);

        // 创建插件目录
        $plugin_base_dir = $this->plugin_dir . '/' . $this->name;
        FileSystem::createDir($plugin_base_dir);

        // 这里开始写入 zmplugin.json
        // 创建插件信息文件
        $zmplugin['name'] = $this->name;
        // 设置版本
        if ($options['plugin-version'] !== null) {
            $zmplugin['version'] = $options['plugin-version'];
        }
        // 设置作者
        if ($options['author'] !== null) {
            $zmplugin['author'] = $options['author'];
        }
        // 判断单文件还是 psr-4 类型
        if ($options['type'] === 'file') {
            // 设置入口文件为 main.php
            $zmplugin['main'] = 'main.php';
        }
        // 到这里就可以写入文件了
        file_put_contents(zm_dir($plugin_base_dir . '/zmplugin.json'), json_encode($zmplugin, JSON_PRETTY_PRINT));

        // 接着写入 main.php
        if ($options['type'] === 'file') {
            $template = file_get_contents(zm_dir(FRAMEWORK_ROOT_DIR . '/src/Templates/main.php.template'));
            $replace = ['{name}' => $this->name];
            $main_php = str_replace(array_keys($replace), array_values($replace), $template);
            file_put_contents(zm_dir($plugin_base_dir . '/main.php'), $main_php);
        } else {
            // 如果是 psr4 就复杂一点，但也不麻烦
            // 先创建 src 目录
            FileSystem::createDir($plugin_base_dir . '/src');
            // 再创建 src/PluginMain.php
            $template = file_get_contents(zm_dir(FRAMEWORK_ROOT_DIR . '/src/Templates/PluginMain.php.template'));
            $replace = [
                '{name}' => $this->name,
                '{namespace}' => $options['namespace'],
                '{class}' => $this->convertClassName(),
            ];
            $main_php = str_replace(array_keys($replace), array_values($replace), $template);
            file_put_contents(zm_dir($plugin_base_dir . '/src/' . $this->convertClassName() . '.php'), $main_php);
            // 写入 composer.json
            $composer_json = [
                'autoload' => [
                    'psr-4' => [
                        $options['namespace'] . '\\' => 'src/',
                    ],
                ],
            ];
            file_put_contents(zm_dir($plugin_base_dir . '/composer.json'), json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            // TODO: 寻找 PHP 运行环境和 Composer 是否在当前目录的情况
            chdir($plugin_base_dir);
            $env = getenv('COMPOSER_EXECUTABLE');
            if ($env === false) {
                $env = 'composer';
            }
            passthru(PHP_BINARY . ' ' . escapeshellcmd($env) . ' dump-autoload');
            chdir(WORKING_DIR);
        }
    }

    /**
     * 根据传入的名称，生成相应的驼峰类名
     */
    public function convertClassName(): string
    {
        $name = $this->name;
        $string = str_replace(['-', '_'], ' ', $name);
        return str_replace(' ', '', ucwords($string));
    }
}
