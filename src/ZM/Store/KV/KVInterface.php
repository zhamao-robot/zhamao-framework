<?php

namespace ZM\Store\KV;

use Psr\SimpleCache\CacheInterface;

interface KVInterface
{
    public static function open(string $name = ''): CacheInterface;
}
