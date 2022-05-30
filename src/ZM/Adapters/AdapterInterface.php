<?php

declare(strict_types=1);

namespace ZM\Adapters;

use ZM\Context\ContextInterface;

interface AdapterInterface
{
    /**
     * 获取适配器名称
     */
    public function getName(): string;

    /**
     * 获取适配器版本
     */
    public function getVersion(): string;

    /**
     * 处理传入请求
     *
     * @param ContextInterface $context 上下文
     */
    public function handleIncomingRequest(ContextInterface $context): void;
}
