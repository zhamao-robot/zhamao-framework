<?php

declare(strict_types=1);

namespace ZM\Plugin\Strategy;

class ComposerStrategy extends PluginInstallStrategy
{
    public function install(array $option = []): bool
    {
        // TODO: Composer 类型的插件还没有实现怎么安装，但很简单。这次 Commit 我偏要鸽！
        $this->error = 'Not implemented';
        return false;
    }
}
