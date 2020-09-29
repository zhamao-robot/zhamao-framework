<?php


use PHPUnit\Framework\TestCase;
use ZM\Store\LightCache;

class LightCacheTest extends TestCase
{
    public function testCache() {
        LightCache::init([
            "size" => 2048,
            "max_strlen" => 4096,
            "hash_conflict_proportion" => 0.6,
        ]);
        //LightCache::set("bool", true);
        $this->assertEquals(true, LightCache::set("2048", 123, 3));
        $this->assertArrayHasKey("2048", LightCache::getAll());
        sleep(3);
        $this->assertArrayNotHasKey("2048", LightCache::getAll());
    }
}
