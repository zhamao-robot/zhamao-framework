<?php

declare(strict_types=1);

namespace Tests\ZM\Plugin;

use Tests\TestCase;
use ZM\Plugin\PluginMeta;

/**
 * @internal
 */
class PluginMetaTest extends TestCase
{
    private string $tmp_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp_dir = sys_get_temp_dir() . '/zm_plugin_meta_test_' . uniqid();
        mkdir($this->tmp_dir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp_dir);
        parent::tearDown();
    }

    public function testGetEntryFileWithComposerMainField(): void
    {
        $this->createFile('composer.json', json_encode([
            'extra' => ['zm-plugin-main' => 'bootstrap.php'],
        ], JSON_THROW_ON_ERROR));
        $this->createFile('bootstrap.php', '<?php');

        $meta = new PluginMeta('test', root_dir: $this->tmp_dir);

        $this->assertSame($this->tmp_dir . '/bootstrap.php', $meta->getEntryFile());
    }

    public function testGetEntryFileFallsBackToMainPhp(): void
    {
        $this->createFile('main.php', '<?php');

        $meta = new PluginMeta('test', root_dir: $this->tmp_dir);

        $this->assertSame($this->tmp_dir . '/main.php', $meta->getEntryFile());
    }

    public function testGetEntryFileWithComposerWithoutExtraField(): void
    {
        $this->createFile('composer.json', json_encode(['name' => 'test'], JSON_THROW_ON_ERROR));
        $this->createFile('main.php', '<?php');

        $meta = new PluginMeta('test', root_dir: $this->tmp_dir);

        $this->assertSame($this->tmp_dir . '/main.php', $meta->getEntryFile());
    }

    public function testGetEntryFileWithBrokenComposerJson(): void
    {
        $this->createFile('composer.json', 'not a json');
        $this->createFile('main.php', '<?php');

        $meta = new PluginMeta('test', root_dir: $this->tmp_dir);

        // composer.json 解析失败时回退 main.php
        $this->assertSame($this->tmp_dir . '/main.php', $meta->getEntryFile());
    }

    public function testGetEntryFileReturnsNullWhenFileNotExists(): void
    {
        $meta = new PluginMeta('test', root_dir: $this->tmp_dir);

        $this->assertNull($meta->getEntryFile());
    }

    public function testGetEntryFileReturnsNullWhenRootDirIsNull(): void
    {
        $meta = new PluginMeta('test');

        $this->assertNull($meta->getEntryFile());
    }

    private function createFile(string $relative_path, string $content): void
    {
        file_put_contents($this->tmp_dir . '/' . $relative_path, $content);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
