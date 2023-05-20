<?php

declare(strict_types=1);

namespace ZM\Utils\CodeGenerator;

use ZM\Exception\FileSystemException;
use ZM\Store\FileSystem;
use ZM\Utils\ZMUtil;

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
     * @param  array               $options 传入的命令行选项
     * @throws FileSystemException
     */
    public function generate(array $options): string
    {
        // 先检查插件目录是否存在，不存在则创建
        FileSystem::createDir($this->plugin_dir);

        // 创建插件目录
        $name = explode('/', $this->name);
        $basename = array_pop($name);
        // 取最后一个，准没错
        $plugin_base_dir = $this->plugin_dir . '/' . $basename;
        // 最后一步：创建插件的根目录！！！
        FileSystem::createDir($plugin_base_dir);

        // 从这里开始写入 composer.json
        $composer = [
            'name' => $this->name,
            'description' => '炸毛框架生成的插件 ' . $this->name,
            'extra' => [
                'zm-plugin-version' => $options['plugin-version'] ?? '1.0.0',
            ],
        ];

        // 设置模板传入的参数列表
        $replace_list = [
            'name' => $this->name,
            'basename' => $basename,
        ];

        if ($options['type'] === 'file') { // 设置入口文件
            $composer['extra']['zm-plugin-main'] = 'main.php';
            $file_contents = $this->replaceTemplate(
                zm_dir(FRAMEWORK_ROOT_DIR . '/src/Templates/main.php.template'),
                $replace_list
            );
            file_put_contents(zm_dir($plugin_base_dir . '/main.php'), $file_contents);
        } elseif ($options['type'] === 'psr4') { // 设置 psr4
            // 如果是 psr4 就复杂一点，但也不麻烦
            // 先创建 src 目录
            FileSystem::createDir($plugin_base_dir . '/src');
            $replace_list['namespace'] = $options['namespace'];
            $replace_list['class'] = $this->convertClassName();
            $file_contents = $this->replaceTemplate(
                zm_dir(FRAMEWORK_ROOT_DIR . '/src/Templates/PluginMain.php.template'),
                $replace_list
            );
            file_put_contents(zm_dir($plugin_base_dir . '/src/' . $this->convertClassName() . '.php'), $file_contents);
            // 设置 autoload
            $composer['autoload'] = [
                'psr-4' => [
                    $options['namespace'] . '\\' => 'src/',
                ],
            ];
        }

        // 创建 composer.json 并更新
        file_put_contents(zm_dir($plugin_base_dir . '/composer.json'), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // TODO: 寻找 PHP 运行环境和 Composer 是否在当前目录的情况
        chdir($plugin_base_dir);
        $env = ZMUtil::getComposerExecutable();
        $cmd = $env === 'composer' ? $env . ' dump-autoload' : PHP_BINARY . ' ' . escapeshellcmd($env) . ' dump-autoload';
        passthru($cmd);
        chdir(WORKING_DIR);
        return $plugin_base_dir;
    }

    /**
     * 根据传入的名称，生成相应的驼峰类名
     */
    public function convertClassName(): string
    {
        $name = explode('/', $this->name);
        $name = array_pop($name);
        $string = str_replace(['-', '_'], ' ', $name);
        return str_replace(' ', '', ucwords($string));
    }

    /**
     * 替换模板参数
     *
     * @param string $zm_dir       路径
     * @param array  $replace_list 替换词列表
     */
    private function replaceTemplate(string $zm_dir, array $replace_list): string
    {
        $template = file_get_contents($zm_dir);
        foreach ($replace_list as $k => $v) {
            $template = str_replace('{' . $k . '}', $v, $template);
        }
        return $template;
    }
}
