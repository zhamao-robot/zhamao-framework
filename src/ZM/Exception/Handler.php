<?php

declare(strict_types=1);

namespace ZM\Exception;

use OneBot\Exception\ExceptionHandler;
use ZM\Exception\Solution\SolutionRepository;

class Handler extends ExceptionHandler
{
    public function __construct()
    {
        parent::__construct();
        /** @noinspection ClassConstantCanBeUsedInspection */
        $ns = 'NunoMaduro\Collision\Handler';
        // TODO: 在 LibOB 发布新版时移除检查
        if (class_exists($ns) && method_exists($this, 'tryEnableCollision')) {
            $this->tryEnableCollision(new SolutionRepository());
        }
    }

    public function handle(\Throwable $e): void
    {
        $this->handle0($e);
    }
}
