<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use Dotenv\Dotenv;
use ZM\Config\Environment;
use ZM\Config\EnvironmentInterface;
use ZM\Config\RuntimePreferences;
use ZM\Config\ZMConfig;

class LoadConfiguration implements Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void
    {
        // TODO: 重新思考容器绑定的加载方式，从而在此处使用 interface
        $env = resolve(Environment::class);
        $this->loadEnvVariables($env);

        new ZMConfig([
            'source' => [
                'paths' => [$preferences->getConfigDir()],
            ],
        ]);
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
