<?php

declare(strict_types=1);

namespace ZM\Command\Plugin;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ZM\Plugin\PluginManager;
use ZM\Store\FileSystem;
use ZM\Utils\ZMRequest;

#[AsCommand(name: 'plugin:install', description: '从 GitHub 或其他 Git 源码托管站安装插件')]
class PluginInstallCommand extends PluginCommand
{
    protected function configure()
    {
        $this->addArgument('address', InputArgument::REQUIRED, '插件地址');

        // 下面是辅助用的，和 server:start 一样
        $this->addOption('config-dir', null, InputOption::VALUE_REQUIRED, '指定其他配置文件目录');
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): int
    {
        $addr = $this->input->getArgument('address');
        $name = ob_uuidgen();
        $plugin_dir = FileSystem::isRelativePath(config('global.plugin.load_dir', 'plugins')) ? (WORKING_DIR . '/' . config('global.plugin.load_dir', 'plugins')) : config('global.plugin.load_dir', 'plugins');
        // 先通过 GitHub API 获取看看存不存在 zmplugin.json
        // 解析 git https 路径中的仓库所有者和仓库名
        $git_url = parse_url($addr);
        if ($git_url['host'] === 'github.com') {
            $path = explode('/', $git_url['path']);
            $owner = $path[1];
            $repo = $path[2];
            if (str_ends_with($repo, '.git')) {
                $repo = substr($repo, 0, -4);
            }
            $api = ZMRequest::get('https://api.github.com/repos/' . $owner . '/' . $repo . '/contents/zmplugin.json', ['User-Agent' => 'ZMFramework']);
            if ($api === false) {
                $this->error('GitHub API 请求失败');
                return static::FAILURE;
            }
            $api = json_decode($api, true);
            if (isset($api['message'])) {
                $this->error('该项目中不存在 zmplugin.json 元信息！');
                return static::FAILURE;
            }
            $contents = implode('', array_map(fn ($x) => base64_decode($x), explode("\n", $api['content'])));
            $json = json_decode($contents, true);
            if (!isset($json['name'])) {
                $this->error('插件元信息内没有名字！');
                return static::FAILURE;
            }
            $plugin_name = $json['name'];
            if (PluginManager::isPluginExists($plugin_name)) {
                $this->error('插件 ' . $plugin_name . ' 已存在，无法再次安装！');
                return static::FAILURE;
            }
        }
        $this->info('正在从 ' . $addr . ' 克隆插件仓库');
        passthru('cd ' . escapeshellarg($plugin_dir) . ' && git clone --depth=1 ' . escapeshellarg($addr) . ' ' . $name, $code);
        if ($code !== 0) {
            $this->error('无法从指定 Git 地址拉取项目，请检查地址名是否正确');
            return static::FAILURE;
        }
        if (!file_exists($plugin_dir . '/' . $name . '/zmplugin.json')) {
            $this->error('项目不存在 zmplugin.json 插件元信息，无法安装');
            // TODO: 使用 rmdir 和 unlink 删除 git 目录
            return static::FAILURE;
        }
        $this->output->writeln('正在检查元信息完整性');
        $getname = json_decode(file_get_contents($plugin_dir . '/' . $name . '/zmplugin.json'), true)['name'] ?? null;
        if ($getname === null) {
            $this->error('无法获取元信息 zmplugin.json');
            return static::FAILURE;
        }
        $code = rename($plugin_dir . '/' . $name, $plugin_dir . '/' . $getname);
        if ($code === false) {
            $this->error('无法重命名文件夹 ' . $name);
            return static::FAILURE;
        }
        if (file_exists($plugin_dir . '/' . $getname . '/composer.json')) {
            $this->info('插件存在 composer.json，正在安装 composer 相关依赖（需要系统环境变量中包含 composer 路径）');
            $cwd = getcwd();
            chdir($plugin_dir . '/' . $getname);
            // 使用内建 Composer
            if (file_exists(WORKING_DIR . '/runtime/composer.phar')) {
                $this->info('使用内建 Composer');
                passthru('php ' . escapeshellarg(WORKING_DIR . '/runtime/composer.phar') . ' install --no-dev', $code);
            } else {
                $this->info('使用系统 Composer');
                passthru('composer install --no-dev', $code);
            }
            chdir($cwd);
            if ($code != 0) {
                $this->error('无法安装 Composer 依赖，请检查 Composer 是否可以正常运行');
                return static::FAILURE;
            }
        }
        $this->info('插件 ' . $getname . ' 安装成功！');
        return static::SUCCESS;
    }
}
