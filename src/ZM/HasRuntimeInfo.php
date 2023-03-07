<?php

declare(strict_types=1);

namespace ZM;

interface HasRuntimeInfo
{
    /**
     * 是否正在可交互終端中运行
     */
    public function runningInInteractiveTerminal(): bool;

    /**
     * 是否正执行单元测试
     */
    public function runningUnitTests(): bool;

    /**
     * 获取或检查运行环境
     *
     * @param array|string ...$environments
     */
    public function environment(...$environments): string|bool;

    public function isDebugMode(): bool;

    public function getLogLevel(): string;

    public function getConfigDir(): string;
}
