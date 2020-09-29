<?php


use PHPUnit\Framework\TestCase;
use ZM\Console\Console;
use ZM\Requests\ZMRequest;
use ZM\Utils\CoroutinePool;

class CoroutinePoolTest extends TestCase
{
    public function testStart() {
        $this->assertTrue(true);
        Console::init(4);
        CoroutinePool::setSize("default", 2);
        CoroutinePool::defaultSize(50);
        for ($i = 0; $i < 59; ++$i) {
            CoroutinePool::go(function () use ($i) {
                //Console::debug("第 $i 个马上进入睡眠...");
                ZMRequest::get("http://localhost:9002/test/ping");
                Console::verbose(strval($i));
            });
        }
    }

    public function testA() {
        $this->assertTrue(true);
    }
}
