<?php

declare(strict_types=1);

namespace ZM\Context\Trait;

use OneBot\Driver\Event\Http\HttpRequestEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ZM\Exception\ZMKnownException;

trait HttpTrait
{
    /**
     * {@inheritDoc}
     */
    public function getRequest(): ServerRequestInterface
    {
        return container()->get('http.request');
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpRequestEvent(): HttpRequestEvent
    {
        $obj = container()->get('http.request.event');
        if (!$obj instanceof HttpRequestEvent) {
            throw new ZMKnownException('E00099', 'current context container event is not HttpRequestEvent');
        }
        return $obj;
    }

    public function withResponse(ResponseInterface $response)
    {
        $this->getHttpRequestEvent()->withResponse($response);
    }
}
