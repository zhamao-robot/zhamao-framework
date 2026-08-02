<?php

declare(strict_types=1);

namespace Tests\ZM\Plugin\OneBot;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\StopException;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\V12\Object\MessageSegment;
use Tests\TestCase;
use Tests\Trait\HasLogger;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Exception\WaitTimeoutException;
use ZM\Plugin\OneBot\OneBot12Adapter;
use ZM\Process\ProcessStateManager;

/**
 * @internal
 */
class OneBot12AdapterTest extends TestCase
{
    use HasLogger;

    private const VALID_TOKEN = 'valid-token';

    protected function setUp(): void
    {
        parent::setUp();
        // 单进程模式下接入连接，避免 ConnectionUtil 写入进程状态文件
        ProcessStateManager::$process_mode = ['worker' => 1];
        $this->startMockLogger();
    }

    protected function tearDown(): void
    {
        // 恢复被覆写的进程模式状态，避免影响其他测试
        ProcessStateManager::$process_mode = [];
        parent::tearDown();
    }

    public function testHandleWSReverseOpenAcceptsCorrectToken(): void
    {
        $event = $this->makeEvent(['Authorization' => 'Bearer ' . self::VALID_TOKEN], ['access_token' => self::VALID_TOKEN]);

        $this->invokeHandleWSReverseOpen($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(101, $event->getResponse()->getStatusCode());
        $this->assertSame('12.qq', $event->getResponse()->getHeaderLine('Sec-WebSocket-Protocol'));
    }

    public function testHandleWSReverseOpenRejectsWrongToken(): void
    {
        $event = $this->makeEvent(['Authorization' => 'Bearer wrong-token'], ['access_token' => self::VALID_TOKEN]);

        $this->expectException(StopException::class);
        $this->invokeHandleWSReverseOpen($event);
    }

    public function testHandleWSReverseOpenRejectsMissingToken(): void
    {
        $event = $this->makeEvent([], ['access_token' => self::VALID_TOKEN]);

        try {
            $this->invokeHandleWSReverseOpen($event);
            $this->fail('Expected StopException was not thrown');
        } catch (StopException) {
            // 预期：无 token 应鉴权失败，返回 401
        }
        $this->assertNotNull($event->getResponse());
        $this->assertSame(401, $event->getResponse()->getStatusCode());
    }

    public function testHandleWSReverseOpenAcceptsQueryToken(): void
    {
        // OneBot 12 规范：URL 查询参数 access_token 直接使用原始 token，无需 Bearer 前缀
        $event = $this->makeEvent([], ['access_token' => self::VALID_TOKEN], ['access_token' => self::VALID_TOKEN]);

        $this->invokeHandleWSReverseOpen($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(101, $event->getResponse()->getStatusCode());
    }

    public function testHandleWSReverseOpenWithoutConfiguredToken(): void
    {
        $event = $this->makeEvent([], []);

        // 未配置 access_token 时不做鉴权，直接接入
        $this->invokeHandleWSReverseOpen($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(101, $event->getResponse()->getStatusCode());
    }

    public function testHandleWSReverseOpenWithClosureToken(): void
    {
        // Closure 返回 true 表示鉴权通过
        $event = $this->makeEvent(['Authorization' => 'Bearer anything'], ['access_token' => fn () => true]);
        $this->invokeHandleWSReverseOpen($event);
        $this->assertNotNull($event->getResponse());
        $this->assertSame(101, $event->getResponse()->getStatusCode());

        // Closure 返回 false 表示鉴权失败
        $event2 = $this->makeEvent(['Authorization' => 'Bearer anything'], ['access_token' => fn () => false]);
        try {
            $this->invokeHandleWSReverseOpen($event2);
            $this->fail('Expected StopException was not thrown');
        } catch (StopException) {
            // 预期
        }
        $this->assertNotNull($event2->getResponse());
        $this->assertSame(401, $event2->getResponse()->getStatusCode());
    }

    public function testHandleWSReverseOpenWithClosureTokenFromQuery(): void
    {
        // Closure 分支在 query 方式下应收到原始 token（不带 Bearer 前缀）
        $received = [];
        $event = $this->makeEvent([], ['access_token' => function ($token) use (&$received) {
            $received[] = $token;
            return $token === self::VALID_TOKEN;
        }], ['access_token' => self::VALID_TOKEN]);

        $this->invokeHandleWSReverseOpen($event);

        $this->assertSame([self::VALID_TOKEN], $received);
        $this->assertNotNull($event->getResponse());
        $this->assertSame(101, $event->getResponse()->getStatusCode());
    }

    public function testMatchCommandArgumentsBoolFromMatchResult(): void
    {
        $adapter = (new \ReflectionClass(OneBot12Adapter::class))->newInstanceWithoutConstructor();
        $cmd = new BotCommand('test');
        $cmd->withArgumentObject(new CommandArgument('flag', type: 'bool', required: true));

        $method = new \ReflectionMethod(OneBot12Adapter::class, 'matchCommandArguments');

        // match_result 中直接命中，无需询问
        $gen = $method->invoke($adapter, ['是'], $cmd);
        $this->assertInstanceOf(\Generator::class, $gen);
        $gen->current();
        $this->assertSame(['flag' => true, '.unnamed' => []], $gen->getReturn());
    }

    public function testMatchCommandArgumentsBoolFromPromptTextSegment(): void
    {
        $adapter = (new \ReflectionClass(OneBot12Adapter::class))->newInstanceWithoutConstructor();
        $cmd = new BotCommand('test');
        $cmd->withArgumentObject(new CommandArgument('flag', type: 'bool', required: true));

        $method = new \ReflectionMethod(OneBot12Adapter::class, 'matchCommandArguments');
        $gen = $method->invoke($adapter, [], $cmd);
        $this->assertInstanceOf(\Generator::class, $gen);

        // 询问返回的消息段是 MessageSegment[] 数组，命中 TRUE_LIST 应能匹配
        $gen->current();
        $gen->send([MessageSegment::text('是')]);
        $this->assertSame(['flag' => true, '.unnamed' => []], $gen->getReturn());

        // FALSE_LIST 场景
        $gen2 = $method->invoke($adapter, [], $cmd);
        $gen2->current();
        $gen2->send([MessageSegment::text('否')]);
        $this->assertSame(['flag' => false, '.unnamed' => []], $gen2->getReturn());
    }

    public function testMatchCommandArgumentsBoolFromPromptSecondTry(): void
    {
        $adapter = (new \ReflectionClass(OneBot12Adapter::class))->newInstanceWithoutConstructor();
        $cmd = new BotCommand('test');
        $cmd->withArgumentObject(new CommandArgument('flag', type: 'bool', required: true));

        $method = new \ReflectionMethod(OneBot12Adapter::class, 'matchCommandArguments');
        $gen = $method->invoke($adapter, [], $cmd);
        $gen->current();

        // 第一次回复未命中，会再次询问
        $prompt = $gen->send([MessageSegment::text('随便')]);
        $this->assertInstanceOf(CommandArgument::class, $prompt);

        // 第二次回复命中
        $gen->send([MessageSegment::text('对')]);
        $this->assertSame(['flag' => true, '.unnamed' => []], $gen->getReturn());
    }

    public function testMatchCommandArgumentsBoolFromPromptTwiceFailed(): void
    {
        $adapter = (new \ReflectionClass(OneBot12Adapter::class))->newInstanceWithoutConstructor();
        $cmd = new BotCommand('test');
        $cmd->withArgumentObject(new CommandArgument('flag', type: 'bool', required: true));

        $method = new \ReflectionMethod(OneBot12Adapter::class, 'matchCommandArguments');
        $gen = $method->invoke($adapter, [], $cmd);
        $gen->current();

        $this->expectException(WaitTimeoutException::class);
        $gen->send([MessageSegment::text('随便')]);
        $gen->send([MessageSegment::text('随便')]);
    }

    private function makeEvent(array $headers, array $socket_config, array $query_params = []): WebSocketOpenEvent
    {
        $headers['Sec-WebSocket-Protocol'] = '12.qq';
        $request = HttpFactory::createServerRequest('GET', 'ws://localhost', $headers, null, '1.1', [], $query_params);
        $event = new WebSocketOpenEvent($request, 1);
        $event->setSocketConfig($socket_config);
        return $event;
    }

    private function invokeHandleWSReverseOpen(WebSocketOpenEvent $event): void
    {
        $adapter = (new \ReflectionClass(OneBot12Adapter::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OneBot12Adapter::class, 'handleWSReverseOpen');
        $method->invoke($adapter, $event);
    }
}
