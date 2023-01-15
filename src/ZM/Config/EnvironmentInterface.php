<?php

declare(strict_types=1);

namespace ZM\Config;

interface EnvironmentInterface
{
    /**
     * 设置环境变量
     */
    public function set(string $name, mixed $value): self;

    /**
     * 获取环境变量
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * 获取所有环境变量
     */
    public function getAll(): array;
}
