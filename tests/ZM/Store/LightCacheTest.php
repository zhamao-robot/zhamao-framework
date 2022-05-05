<?php

declare(strict_types=1);

namespace Tests\ZM\Store;

use PHPUnit\Framework\TestCase;
use ZM\Store\LightCache;

/**
 * @internal
 */
class LightCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        LightCache::unset('test');
        LightCache::unset('test2');
    }

    public function testSetAndGet(): void
    {
        $result = LightCache::set('test', 'value');

        $this->assertTrue($result);
        $this->assertSame('value', LightCache::get('test'));
    }

    public function testUnset(): void
    {
        LightCache::set('test', 'value');
        $this->assertTrue(LightCache::unset('test'));
        $this->assertNull(LightCache::get('test'));
        $this->assertFalse(LightCache::unset('test'));
    }

    public function testGetAll(): void
    {
        LightCache::set('test', 'value');
        LightCache::set('test2', 'value2');

        $this->assertSame(['test' => 'value', 'test2' => 'value2'], LightCache::getAll());
    }

    public function testItemCanExpire(): void
    {
        LightCache::set('test', 'value', 1);
        $this->assertSame('value', LightCache::get('test'));
        zm_sleep(2);
        $this->assertNull(LightCache::get('test'));
    }

    public function testGetExpire(): void
    {
        LightCache::set('test', 'value', 10);
        $this->assertSame(10, LightCache::getExpire('test'));
    }

    public function testGetExpireTS(): void
    {
        LightCache::set('test', 'value', 10);
        $this->assertSame(time() + 10, LightCache::getExpireTS('test'));
    }

    public function testIsset(): void
    {
        LightCache::set('test', 'value');
        $this->assertTrue(LightCache::isset('test'));
        $this->assertFalse(LightCache::isset('test2'));
    }

    public function testGetMemoryUsage(): void
    {
        LightCache::set('test', 'value');
        $this->assertGreaterThan(0, LightCache::getMemoryUsage());
    }

    public function testUpdate(): void
    {
        LightCache::set('test', 'value');
        $this->assertTrue(LightCache::update('test', 'value2'));
        $this->assertSame('value2', LightCache::get('test'));
    }
}
