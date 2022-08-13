<?php

declare(strict_types=1);

namespace ZM\Middleware;

interface PipelineInterface
{
    public function handle(callable $callback, ...$params);
}
