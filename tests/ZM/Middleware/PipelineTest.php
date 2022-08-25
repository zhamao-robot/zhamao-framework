<?php

declare(strict_types=1);

namespace Tests\ZM\Middleware;

use PHPUnit\Framework\TestCase;
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
            ->then(function (string $value) {
                return $value;
            });
        $this->assertEquals('APP', $a);
    }
}
