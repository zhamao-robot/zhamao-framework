<?php

declare(strict_types=1);

namespace Tests\Trait;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait HasVirtualFileSystem
{
    private vfsStreamDirectory $vfs;

    private function setUpVfs(string $dir = 'root', array $structure = []): void
    {
        $this->vfs = vfsStream::setup($dir, structure: $structure);
    }
}
