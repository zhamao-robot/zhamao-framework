<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Event\Http\HttpRequestEvent;
use OneBot\Driver\Event\StopException;
use OneBot\Http\HttpFactory;
use OneBot\Util\Singleton;

class HttpEventListener
{
    use Singleton;

    /**
     * @throws StopException
     */
    public function onRequest(HttpRequestEvent $event)
    {
        $msg = 'Hello from ' . $event->getSocketFlag();
        $res = HttpFactory::getInstance()->createResponse()->withBody(HttpFactory::getInstance()->createStream($msg));
        $event->withResponse($res);
    }
}
