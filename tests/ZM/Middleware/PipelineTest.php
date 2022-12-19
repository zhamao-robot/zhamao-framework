<?php

declare(strict_types=1);

namespace Tests\ZM\Middleware;

use PHPUnit\Framework\TestCase;
use ZM\Logger\ConsoleLogger;
use ZM\Middleware\Pipeline;
use ZM\Middleware\TimerMiddleware;

/**
 * @internal
 */
class PipelineTest extends TestCase
{
    public function setUp(): void
    {
        ob_logger_register(new ConsoleLogger('debug'));
    }

    public function tearDown(): void
    {
        ob_logger_register(new ConsoleLogger('error'));
    }

    public function testPipeline()
    {
        $pipe = new Pipeline();
        $a = $pipe->send('APP')
            ->through([TimerMiddleware::class])
            ->then(fn (string $value) => $value);
        $this->assertEquals('APP', $a);
    }
}
