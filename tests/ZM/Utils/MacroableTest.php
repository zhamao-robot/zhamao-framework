<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Exception\MethodNotFoundException;
use ZM\Utils\Macroable;

/**
 * @internal
 */
class MacroableTest extends TestCase
{
    private $macroable;

    protected function setUp(): void
    {
        $this->macroable = new class() {
            use Macroable;

            private $secret = 'secret';

            private static function anotherSecret()
            {
                return 'another secret';
            }
        };
    }

    public function testMacroCanBeDefined(): void
    {
        $this->macroable::macro('getSecret', function () {
            return $this->secret;
        });

        $this->assertEquals('secret', $this->macroable->getSecret());
    }

    public function testMacroCanBeDefinedStatically(): void
    {
        $this->macroable::macro('getSecret', static function () {
            return 'static secret';
        });

        $this->assertEquals('static secret', $this->macroable::getSecret());
    }

    public function testMacroCanBeDefinedWithParameters(): void
    {
        $this->macroable::macro('getParam', function ($param) {
            return $param;
        });

        $this->assertEquals('param', $this->macroable->getParam('param'));
    }

    public function testExceptionIsThrownWhenMacroIsNotDefined(): void
    {
        $this->expectException(MethodNotFoundException::class);

        $this->macroable->unknownMacro();
    }
}
