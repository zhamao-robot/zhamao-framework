<?php

declare(strict_types=1);

namespace Tests\ZM\Store\KV\Redis;

use OneBot\Driver\Interfaces\PoolInterface;
use Tests\TestCase;
use ZM\Store\KV\Redis\KVRedis;
use ZM\Store\KV\Redis\RedisPool;

/**
 * @internal
 */
class KVRedisTest extends TestCase
{
    private FakeRedisPool $pool;

    private FakeRedisClient $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new FakeRedisClient();
        $this->pool = new FakeRedisPool(1, FakeRedisClient::class, $this->redis);
        RedisPool::$pools = ['default' => $this->pool];
    }

    protected function tearDown(): void
    {
        RedisPool::$pools = [];
        parent::tearDown();
    }

    public function testClearDeletesAllPrefixedKeysByScan(): void
    {
        $kv = new KVRedis('test');
        $kv->set('a', '1');
        $kv->set('b', '2');
        $kv->set('c', '3');
        $kv->set('d', '4');
        $kv->set('e', '5');
        // 不属于当前 KV 库的键不应该被清除
        $this->redis->store['other:key'] = 'x';

        $this->assertTrue($kv->clear());

        $this->assertFalse($kv->has('a'));
        $this->assertFalse($kv->has('b'));
        $this->assertFalse($kv->has('c'));
        $this->assertFalse($kv->has('d'));
        $this->assertFalse($kv->has('e'));
        $this->assertArrayHasKey('other:key', $this->redis->store);
        $this->assertGreaterThan(1, $this->redis->scan_calls, 'clear 应使用 SCAN 迭代删除而非单次 DEL');
    }

    public function testClearReturnsTrueWhenNoKeysMatched(): void
    {
        // PSR-16 规定 clear() 执行成功即返回 true，即使没有匹配到任何键
        $kv = new KVRedis('test');
        $this->assertTrue($kv->clear());
    }
}

class FakeRedisClient
{
    /** @var array<string, string> 模拟的 Redis 键值存储 */
    public array $store = [];

    /** @var int SCAN 调用次数 */
    public int $scan_calls = 0;

    /** @var null|array 本次 SCAN 迭代的快照列表（模拟 SCAN 基于稳定快照迭代的语义） */
    private ?array $snapshot = null;

    private int $snapshot_offset = 0;

    public function set(string $key, $value, $ttl = null): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function get(string $key): false|string
    {
        return $this->store[$key] ?? false;
    }

    public function exists(string ...$keys): bool|int
    {
        $count = 0;
        foreach ($keys as $key) {
            if (isset($this->store[$key])) {
                ++$count;
            }
        }
        return $count;
    }

    public function del(array|string $key, ...$other_keys): int
    {
        $deleted = 0;
        foreach (array_merge(is_array($key) ? $key : [$key], $other_keys) as $k) {
            if (isset($this->store[$k])) {
                unset($this->store[$k]);
                ++$deleted;
            }
        }
        return $deleted;
    }

    public function scan(&$iterator, ?string $pattern = null, int $count = 0): array|false
    {
        // 游标为 0 或首次调用时，对匹配的键建立快照
        if ($iterator === 0 || $iterator === null || $this->snapshot === null) {
            $prefix = rtrim($pattern ?? '', '*');
            $this->snapshot = array_values(array_filter(array_keys($this->store), fn ($k) => str_starts_with($k, $prefix)));
            $this->snapshot_offset = 0;
        }
        $batch = array_slice($this->snapshot, $this->snapshot_offset, 2);
        $this->snapshot_offset += count($batch);
        ++$this->scan_calls;
        // 模拟 SCAN 游标：还有剩余键时游标非 0，遍历完则为 0
        $iterator = $this->snapshot_offset < count($this->snapshot) ? 1 : 0;
        return $batch;
    }
}

class FakeRedisPool implements PoolInterface
{
    private object $redis;

    public function __construct(int $size = 1, string $construct_class = '', ...$args)
    {
        $this->redis = $args[0];
    }

    public function __destruct() {}

    public function get(): object
    {
        return $this->redis;
    }

    public function put(object $object): bool
    {
        return true;
    }

    public function getFreeCount(): int
    {
        return 1;
    }
}
