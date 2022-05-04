<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use Throwable;
use ZM\Console\Console;
use ZM\Utils\Terminal;

/**
 * @internal
 */
class TerminalTest extends TestCase
{
    public function testInit()
    {
        Console::setLevel(4);
        Terminal::init();
        $this->expectOutputRegex('/Initializing\ Terminal/');
    }

    /**
     * @throws Throwable
     */
    public function testExecuteCommand()
    {
        Console::setLevel(2);
        Terminal::executeCommand('echo zhamao-framework');
        $this->expectOutputRegex('/zhamao-framework/');
    }
}
