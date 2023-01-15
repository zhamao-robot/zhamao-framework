<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use Dotenv\Dotenv;
use OneBot\Driver\Workerman\Worker;
use ZM\Config\Environment;
use ZM\Config\EnvironmentInterface;
use ZM\Config\ZMConfig;

class LoadConfiguration
{
    public function bootstrap(array $config): void
    {
        // TODO: 重新思考容器绑定的加载方式，从而在此处使用 interface
        $env = resolve(Environment::class);
        $this->loadEnvVariables($env);

        $config_i = config();
        $config_i->addConfigPath($this->getConfigDir($config));
        $config_i->setEnvironment($this->getConfigEnvironment($config));
        $this->parseArgvToConfig($config, $config_i);
    }

    private function getConfigDir(array $config): string
    {
        $config_dir = $config['config-dir'];
        // 默认配置文件目录
        $find_dir = [
            WORKING_DIR . '/config',
            SOURCE_ROOT_DIR . '/config',
        ];
        // 如果启动参数指定了配置文件目录，则优先使用
        if ($config_dir !== null) {
            array_unshift($find_dir, $config_dir);
        }

        // 遍历目录，找到第一个存在的目录
        foreach ($find_dir as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }

        // 如果没有找到目录，则抛出异常
        throw new \RuntimeException('No config directory found');
    }

    private function getConfigEnvironment(array $config): string
    {
        return $config['env'] ?? 'development';
    }

    private function parseArgvToConfig(array $argv, ZMConfig $config): void
    {
        foreach ($argv as $x => $y) {
            // 当值为 true/false 时，表示该参数为可选参数。当值为 null 时，表示该参数必定会有一个值，如果是 null，说明没指定
            if ($y === false || is_null($y)) {
                continue;
            }
            switch ($x) {
                case 'driver':      // 动态设置驱动类型
                    $config->set('global.driver', $y);
                    break;
                case 'worker-num':  // 动态设置 Worker 数量
                    $config->set('global.swoole_options.swoole_set.worker_num', (int) $y);
                    $config->set('global.workerman_options.workerman_worker_num', (int) $y);
                    break;
                case 'daemon':      // 启动为守护进程
                    $config->set('global.swoole_options.swoole_set.daemonize', 1);
                    Worker::$daemonize = true;
                    break;
            }
        }
    }

    private function loadEnvVariables(EnvironmentInterface $env): void
    {
        $dotenv_path = $env->get('DOTENV_PATH', SOURCE_ROOT_DIR . '/.env');

        if (!file_exists($dotenv_path)) {
            return;
        }

        $path = dirname($dotenv_path);
        $file = basename($dotenv_path);

        foreach (Dotenv::createImmutable($path, $file)->load() as $key => $value) {
            $env->set($key, $value);
        }
    }
}
