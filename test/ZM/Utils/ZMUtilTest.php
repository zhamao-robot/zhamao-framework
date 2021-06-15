<?php


namespace ZM\Utils;


use Module\Example\Hello;
use Module\Middleware\TimerMiddleware;
use PHPUnit\Framework\TestCase;
use ZM\Framework;

class ZMUtilTest extends TestCase
{
    public function testGetClassesPsr4() {
        $this->assertContains(Hello::class, ZMUtil::getClassesPsr4(DataProvider::getSourceRootDir()."/src/Module", "Module"));
        $this->assertContains(TimerMiddleware::class, ZMUtil::getClassesPsr4(DataProvider::getSourceRootDir()."/src/Module", "Module"));
        $this->assertContains(Framework::class, ZMUtil::getClassesPsr4(DataProvider::getSourceRootDir()."/src/ZM", "ZM"));
    }
}