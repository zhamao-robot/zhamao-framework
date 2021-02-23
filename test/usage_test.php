<?php

use ZM\Exception\ZMException;
use ZM\Store\LightCache;

LightCache::getMemoryUsage();
try {
    LightCache::getExpire('1');
} catch (ZMException $e) {
}