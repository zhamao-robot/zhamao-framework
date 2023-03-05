<?php

declare(strict_types=1);

namespace ZM\Middleware;

trait MiddlewareArgTrait
{
    protected array $args = [];

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }
}
