<?php


namespace ZMTest\Testing;


use Module\Example\Hello;
use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;
use ZM\Utils\ZMUtil;

class ModuleTest extends TestCase
{
    protected function setUp(): void {
        ZMConfig::setDirectory(realpath(__DIR__."/../Mock"));
        set_coroutine_params([]);
        require_once __DIR__ . '/../../../src/ZM/global_defines.php';
    }

    public function testCtx() {
        $r = ZMUtil::getModInstance(Hello::class);
        ob_start();
        $r->randNum(["随机数", "1", "5"]);
        $out = ob_get_clean();
        $this->assertEquals("随机数是：1\n", $out);
    }
}
