<?php

declare(strict_types=1);

namespace ZM\Command;

trait SourceLoadModeOnly
{
    protected function shouldExecute(): bool
    {
        if (LOAD_MODE === LOAD_MODE_SRC) {
            return true;
        }

        if (method_exists($this, 'error')) {
            $this->error('此命令只能在源码模式下使用！');
        }
        return false;
    }
}
