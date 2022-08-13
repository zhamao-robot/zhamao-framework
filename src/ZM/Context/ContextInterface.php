<?php

declare(strict_types=1);

namespace ZM\Context;

use OneBot\Driver\Event\Http\HttpRequestEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ContextInterface
{
    /**
     * 获取 Http Request 请求对象
     */
    public function getRequest(): ServerRequestInterface;

    /**
     * 获取 Http 请求事件对象
     */
    public function getHttpRequestEvent(): HttpRequestEvent;

    /**
     * 使用 Response 对象响应 Http 请求
     * Wrapper of HttpRequestEvent::withResponse method
     *
     * @param ResponseInterface $response 响应对象
     */
    public function withResponse(ResponseInterface $response);
}
