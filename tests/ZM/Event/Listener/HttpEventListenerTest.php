<?php

declare(strict_types=1);

namespace Tests\ZM\Event\Listener;

use Choir\Http\ServerRequest;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Route;
use Tests\TestCase;
use Tests\Trait\HasVirtualFileSystem;
use ZM\Event\Listener\HttpEventListener;
use ZM\Utils\HttpUtil;

/**
 * @internal
 */
class HttpEventListenerTest extends TestCase
{
    use HasVirtualFileSystem;

    public function fakeHandler(): ?string
    {
        return 'I am here to greet';
    }

    public function testHandleNotAllowedRoute(): void
    {
        $this->addRoute($this->mockHandler(false), 'fakeHandler');

        $event = $this->mockRequestEvent(new ServerRequest('DELETE', '/test/get'), true);
        HttpEventListener::getInstance()->onRequest999($event);
    }

    public function testHandleNotFoundRoute(): void
    {
        $this->addRoute($this->mockHandler(false), 'fakeHandler');

        $event = $this->mockRequestEvent(new ServerRequest('GET', '/test/not-found'), false);
        HttpEventListener::getInstance()->onRequest999($event);
    }

    public function testHandleFoundRoute(): void
    {
        $this->addRoute('', [$this->mockHandler(true), 'fakeHandler']);

        $event = $this->mockRequestEvent(new ServerRequest('GET', '/test/get'), true);
        HttpEventListener::getInstance()->onRequest999($event);
    }

    public function testHandleFoundRouteWithException(): void
    {
        $this->addRoute('', [$this->mockHandler(true, fn () => null), 'fakeHandler']);

        $event = $this->mockRequestEvent(new ServerRequest('GET', '/test/get'), true);
        HttpEventListener::getInstance()->onRequest999($event);
    }

    public function testHandleStaticFile(): void
    {
        $this->setUpVfs('static', [
            'test.txt' => 'Hello, world!',
        ]);
        $event = $this->mockRequestEvent(new ServerRequest('GET', '/test.txt'), true);

        $old_conf = config('global.file_server.document_root');
        config(['global.file_server.document_root' => $this->vfs->url()]);
        HttpEventListener::getInstance()->onRequest1($event);
        config(['global.file_server.document_root' => $old_conf]);
    }

    private function addRoute($class, $method): void
    {
        HttpUtil::getRouteCollection()->remove('test.get');
        $route = new Route('/test/get', ['_class' => $class, '_method' => $method], methods: ['GET']);
        HttpUtil::getRouteCollection()->add('test.get', $route);
    }

    private function mockRequestEvent(ServerRequest $request, bool $should_have_response): \HttpRequestEvent
    {
        $event = $this->prophesize(\HttpRequestEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn(null);
        if ($should_have_response) {
            $event->withResponse(Argument::type(ResponseInterface::class))
                ->will(function ($args) use ($event) {
                    $event->getResponse()->willReturn($args[0]);
                    return $event->reveal();
                })
                ->shouldBeCalledOnce();
        } else {
            $event->withResponse(Argument::type(ResponseInterface::class))->shouldNotBeCalled();
        }
        return $event->reveal();
    }

    private function mockHandler(bool $should_be_called, callable $callback = null): self
    {
        $handler = $this->prophesize(self::class);
        if ($should_be_called) {
            $handler->fakeHandler()->will(fn () => $callback ? $callback() : 'OK!')->shouldBeCalledOnce();
        } else {
            $handler->fakeHandler(Argument::cetera())->shouldNotBeCalled();
        }
        return $handler->reveal();
    }
}
