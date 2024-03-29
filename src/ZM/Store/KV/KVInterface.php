<?php

declare(strict_types=1);

namespace ZM\Store\KV;

use Psr\SimpleCache\CacheInterface;

interface KVInterface extends CacheInterface
{
    public static function open(string $name = ''): CacheInterface;
}
