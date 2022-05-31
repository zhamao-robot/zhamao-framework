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

    /**
     * 处理传出请求
     *
     * @param string      $action 动作名称
     * @param array       $params 动作参数
     * @param null|string $echo   回声
     * @param array       $extra  额外参数
     *
     * @return mixed
     */
    public function handleOutgoingRequest(string $action, array $params, string $echo = null, array $extra = []);
}
