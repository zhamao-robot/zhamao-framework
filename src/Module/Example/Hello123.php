<?php

declare(strict_types=1);

namespace Module\Example;

use ZM\Annotation\Framework\Setup;
use ZM\Annotation\Http\Route;
use ZM\Annotation\Middleware\Middleware;
use ZM\Middleware\TimerMiddleware;

class Hello123
{
    #[Setup]
    public function onRequest()
    {
        echo "OK\n";
    }

    #[Route('/route', request_method: ['GET'])]
    #[Middleware(TimerMiddleware::class)]
    public function route()
    {
        return 'Hello Zhamao！This is the first 3.0 page！';
    }
}
