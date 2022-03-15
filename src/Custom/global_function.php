<?php

declare(strict_types=1);

use ZM\Utils\CoroutinePool;

function pgo(callable $func, string $name = 'default')
{
    CoroutinePool::go($func, $name);
}
