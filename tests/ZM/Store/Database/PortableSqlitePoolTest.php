<?php

declare(strict_types=1);

namespace Tests\ZM\Store\Database;

use Tests\TestCase;
use ZM\Store\Database\DBException;
use ZM\Store\Database\DBPool;

/**
 * @internal
 */
class PortableSqlitePoolTest extends TestCase
{
    private string $tmp_dir;

    private mixed $old_data_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->old_data_dir = config('global.data_dir');
        $this->tmp_dir = sys_get_temp_dir() . '/zm-portable-test-' . uniqid();
        config(['global.data_dir' => $this->tmp_dir]);
        config(['global.database.portable_pool_size' => 4]);
        config(['global.database.portable_pool_max' => 64]);
        DBPool::resetPortableSQLite();
    }

    protected function tearDown(): void
    {
        DBPool::resetPortableSQLite();
        config(['global.data_dir' => $this->old_data_dir]);
        self::removeDir($this->tmp_dir);
        parent::tearDown();
    }

    public function testKeepAliveReusesPooledConnection(): void
    {
        $db = zm_sqlite('test-a.db');
        $db->executeStatement('CREATE TABLE t (id INTEGER PRIMARY KEY, v TEXT)');
        $db->insert('t', ['v' => 'hello']);
        $this->assertEquals('hello', $db->fetchOne('SELECT v FROM t WHERE id = 1'));
        unset($db);

        // 再次获取（keep_alive 走连接池），数据仍然可读
        $db2 = zm_sqlite('test-a.db');
        $this->assertEquals('hello', $db2->fetchOne('SELECT v FROM t WHERE id = 1'));
        unset($db2);

        // 注册表中只存在一个连接池
        $pools = self::getPortablePools();
        $this->assertCount(1, $pools);
        $this->assertArrayHasKey($this->tmp_dir . '/db/test-a.db', $pools);
    }

    public function testPoolExhaustionFallsBackToTemporaryConnection(): void
    {
        config(['global.database.portable_pool_size' => 1]);
        $db1 = zm_sqlite('test-b.db');
        $db1->executeStatement('CREATE TABLE t (v TEXT)');
        /** @var \OneBot\Driver\Interfaces\PoolInterface $pool */
        $pool = current(self::getPortablePools());
        $this->assertSame(0, $pool->getFreeCount());

        // 池内连接全部被占用时，第二次获取走临时连接，不阻塞
        $db2 = zm_sqlite('test-b.db');
        $db2->executeStatement('INSERT INTO t VALUES (\'x\')');
        $this->assertEquals('x', $db1->fetchOne('SELECT v FROM t'));
        unset($db1, $db2);

        // 归还后池容量恢复
        $this->assertSame(1, $pool->getFreeCount());
    }

    public function testLruEvictsOldestPool(): void
    {
        config(['global.database.portable_pool_max' => 1]);
        $db1 = zm_sqlite('test-c1.db');
        $db1->executeStatement('CREATE TABLE t (v TEXT)');
        unset($db1);

        $db2 = zm_sqlite('test-c2.db');
        $db2->executeStatement('CREATE TABLE t (v TEXT)');
        unset($db2);

        // 达到上限后，最久未访问的连接池被淘汰
        $pools = self::getPortablePools();
        $this->assertCount(1, $pools);
        $this->assertArrayHasKey($this->tmp_dir . '/db/test-c2.db', $pools);
        $this->assertArrayNotHasKey($this->tmp_dir . '/db/test-c1.db', $pools);
    }

    public function testTemporaryConnectionWithoutKeepAlive(): void
    {
        $db = zm_sqlite('test-d.db', true, false);
        $db->executeStatement('CREATE TABLE t (v TEXT)');
        $db->insert('t', ['v' => 'y']);
        $this->assertEquals('y', $db->fetchOne('SELECT v FROM t'));
        unset($db);

        // keep_alive 为 false 时不注册连接池
        $this->assertSame([], self::getPortablePools());
    }

    public function testCreateNewFalseThrowsWhenFileMissing(): void
    {
        // DBAL 连接是懒加载的，首次执行查询时才建立连接
        $db = zm_sqlite('not-exists.db', false, false);
        $this->expectException(DBException::class);
        $db->executeStatement('SELECT 1');
    }

    public function testWalAndBusyTimeoutApplied(): void
    {
        $db = zm_sqlite('test-e.db');
        $db->executeStatement('CREATE TABLE t (v TEXT)');
        $this->assertEquals('wal', $db->fetchOne('PRAGMA journal_mode'));
        $this->assertEquals(5000, $db->fetchOne('PRAGMA busy_timeout'));
        unset($db);
    }

    private static function getPortablePools(): array
    {
        $prop = new \ReflectionProperty(DBPool::class, 'portable_pools');
        /* @phpstan-ignore-next-line 反射读取私有静态属性 */
        return $prop->getValue();
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
