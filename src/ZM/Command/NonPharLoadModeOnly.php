<?php

declare(strict_types=1);

namespace ZM\Command;

trait NonPharLoadModeOnly
{
    protected function shouldExecute(): bool
    {
        if (\Phar::running() === '') {
            return true;
        }

        if (method_exists($this, 'error')) {
            $this->error('此命令只能在非 Phar 模式下使用！');
        }
        return false;
    }
}
