<?php

declare(strict_types=1);

namespace ZM\Plugin;

use ZM\Exception\PluginException;

/**
 * 插件的元信息对象
 */
class PluginMeta implements \JsonSerializable
{
    /** @var string 插件名称 */
    private string $name;

    /** @var string 插件版本 */
    private string $version;

    /** @var string 插件描述 */
    private string $description;

    /** @var null|string 插件的根目录 */
    private ?string $root_dir;

    /** @var int 插件类型 */
    private int $plugin_type;

    private bool $enabled = true;

    private ?ZMPlugin $entity = null;

    public function __construct(string $name, string $version = '1.0-dev', string $description = '', int $plugin_type = ZM_PLUGIN_TYPE_NATIVE, ?string $root_dir = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->description = $description;
        $this->plugin_type = $plugin_type;
        $this->root_dir = $root_dir;
    }

    public function enablePlugin(): void
    {
        $this->enabled = true;
    }

    public function disablePlugin(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function bindEntity(ZMPlugin $plugin): void
    {
        $this->entity = $plugin;
    }

    /**
     * 获取该插件的入口文件（如果存在的话）
     */
    public function getEntryFile(): ?string
    {
        // 没传入插件目录的话，直接返回空
        if ($this->root_dir === null) {
            return null;
        }
        // 首先看看元信息中有没有 main 字段指定，没有就从 default 里找
        $main = zm_dir($this->root_dir . '/' . ($this->metas['main'] ?? 'main.php'));
        if (file_exists($main)) {
            return $main;
        }
        return null;
    }

    /**
     * 获取该插件的 Composer 自动加载文件（如果存在的话）
     */
    public function getAutoloadFile(): ?string
    {
        // 没传入插件目录的话，直接返回空
        if ($this->root_dir === null) {
            return null;
        }
        return match ($this->plugin_type) {
            ZM_PLUGIN_TYPE_PHAR, ZM_PLUGIN_TYPE_SOURCE => file_exists($dir = zm_dir($this->root_dir . '/vendor/autoload.php')) ? $dir : null,
            ZM_PLUGIN_TYPE_NATIVE, ZM_PLUGIN_TYPE_COMPOSER => zm_dir(SOURCE_ROOT_DIR . '/vendor/autoload.php'),
            default => null,
        };
    }

    /**
     * 获取该插件的 Composer 自动加载 PSR-4 列表
     *
     * @throws PluginException 无法加载 composer.json 时抛出异常
     */
    public function getAutoloadPsr4(): array
    {
        // 没传入插件目录的话，直接返回空
        if ($this->root_dir === null) {
            return [];
        }
        // 先找有没有 composer.json，没有的话就返回空列表
        if (!file_exists(zm_dir($this->root_dir . '/composer.json'))) {
            return [];
        }
        // 有，但是 composer.json 是坏的，抛出一个异常
        if (($composer = json_decode(file_get_contents(zm_dir($this->root_dir . '/composer.json')), true)) === null) {
            throw new PluginException("Bad composer.json in plugin {$this->name}");
        }
        return $composer['autoload']['psr-4'] ?? [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRootDir(): string
    {
        return $this->root_dir;
    }

    public function setRootDir(string $root_dir): void
    {
        $this->root_dir = $root_dir;
    }

    public function getPluginType(): int
    {
        return $this->plugin_type;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
        ];
    }

    public function getEntity(): ?ZMPlugin
    {
        return $this->entity;
    }
}
