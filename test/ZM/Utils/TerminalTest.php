<?php

namespace ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;

class TerminalTest extends TestCase
{
    public function setUp(): void {
    }

    public function testExecuteCommand() {
        ob_start();
        Terminal::executeCommand("logtest");
        $this->assertStringContainsString("debug msg", ob_get_clean());
    }

    public function testBc() {
        ob_start();
        Terminal::executeCommand("bc ".base64_encode("echo 'hello';"));
        $this->assertStringContainsString("hello", ob_get_clean());
    }
}
