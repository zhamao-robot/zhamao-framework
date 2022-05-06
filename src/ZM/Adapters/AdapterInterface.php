<?php

declare(strict_types=1);

namespace ZM\Adapters;

use Swoole\WebSocket\Frame;
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
     * @param Frame            $frame   WebSocket消息帧
     * @param ContextInterface $context 上下文
     */
    public function handleIncomingRequest(Frame $frame, ContextInterface $context): void;
}
