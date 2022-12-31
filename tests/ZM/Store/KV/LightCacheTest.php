<?php

declare(strict_types=1);

namespace Tests\ZM\Store\KV;

use PHPUnit\Framework\TestCase;
use ZM\Store\KV\LightCache;

/**
 * @internal
 */
class LightCacheTest extends TestCase
{
    public function testRemoveSelf()
    {
        $a = LightCache::open('asd');
        $this->assertInstanceOf(LightCache::class, $a);
        /* @phpstan-ignore-next-line */
        $this->assertTrue($a->removeSelf());
    }

    public function testSet()
    {
        $this->assertTrue(LightCache::open()->set('test123', 'help'));
    }

    public function testIsset()
    {
        $this->assertFalse(LightCache::open()->isset('test111'));
    }

    public function testGet()
    {
        LightCache::open('ppp')->set('hello', 'world');
        $this->assertSame(LightCache::open('ppp')->get('hello', 'ffff'), 'world');
    }

    public function testUnset()
    {
        $kv = LightCache::open('sss');
        $kv->set('test', 'test');
        $this->assertSame($kv->get('test'), 'test');
        $kv->unset('test');
        $this->assertNull($kv->get('test'));
    }
}
