<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */ #plain

//这里写你的全局函数
/**
 * @param callable $func
 * @param string $name
 * @noinspection PhpUnused
 */
function pgo(callable $func, $name = "default") {
    \ZM\Utils\CoroutinePool::go($func, $name);
}
