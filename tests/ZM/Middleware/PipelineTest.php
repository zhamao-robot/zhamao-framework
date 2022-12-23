<?php

declare(strict_types=1);

namespace Tests\ZM\Middleware;

use Tests\TestCase;
use ZM\Middleware\Pipeline;
use ZM\Middleware\TimerMiddleware;

/**
 * @internal
 */
class PipelineTest extends TestCase
{
    public function testPipeline()
    {
        $pipe = new Pipeline();
        $a = $pipe->send('APP')
            ->through([TimerMiddleware::class])
            ->then(fn (string $value) => $value);
        $this->assertEquals('APP', $a);
    }
}
