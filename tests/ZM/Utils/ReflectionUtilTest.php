<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Utils\ReflectionUtil;

/**
 * @internal
 */
class ReflectionUtilTest extends TestCase
{
    public function testDetermineStaticMethod(): void
    {
        $this->assertFalse(ReflectionUtil::isNonStaticMethod([ReflectionUtilTestClass::class, 'staticMethod']));
    }

    public function testDetermineNonStaticMethod(): void
    {
        $this->assertTrue(ReflectionUtil::isNonStaticMethod([ReflectionUtilTestClass::class, 'method']));
    }

    public function testGetParameterClassName(): void
    {
        $class = new \ReflectionClass(ReflectionUtilTestClass::class);
        $method = $class->getMethod('method');
        [$string_parameter, $object_parameter] = $method->getParameters();

        $this->assertNull(ReflectionUtil::getParameterClassName($string_parameter));
        $this->assertSame(ReflectionUtilTestClass::class, ReflectionUtil::getParameterClassName($object_parameter));
    }

    /**
     * @dataProvider provideTestVariableToString
     * @param mixed $variable
     */
    public function testVariableToString($variable, string $expected): void
    {
        $this->assertSame($expected, ReflectionUtil::variableToString($variable));
    }

    public function provideTestVariableToString(): array
    {
        return [
            'callable' => [[new ReflectionUtilTestClass(), 'method'], ReflectionUtilTestClass::class . '@method'],
            'static callable' => [[ReflectionUtilTestClass::class, 'staticMethod'], ReflectionUtilTestClass::class . '::staticMethod'],
            'closure' => [\Closure::fromCallable([$this, 'testVariableToString']), 'closure'],
            'string' => ['string', 'string'],
            'array' => [['123', '42', 'hello', 122], 'array["123","42","hello",122]'],
            'object' => [new \stdClass(), 'stdClass'],
            'resource' => [fopen('php://memory', 'rb'), 'resource(stream)'],
            'null' => [null, 'null'],
            'boolean 1' => [true, 'true'],
            'boolean 2' => [false, 'false'],
            'float' => [123.456, '123.456'],
            'integer' => [123, '123'],
        ];
    }

    /**
     * @dataProvider provideTestGetCallReflector
     * @param mixed $callback
     */
    public function testGetCallReflector($callback, \ReflectionFunctionAbstract $expected): void
    {
        $this->assertEquals($expected, ReflectionUtil::getCallReflector($callback));
    }

    public function provideTestGetCallReflector(): array
    {
        $closure = function () {
        };

        return [
            'callable' => [[new ReflectionUtilTestClass(), 'method'], new \ReflectionMethod(ReflectionUtilTestClass::class, 'method')],
            'static callable' => [[ReflectionUtilTestClass::class, 'staticMethod'], new \ReflectionMethod(ReflectionUtilTestClass::class, 'staticMethod')],
            'class::method' => [ReflectionUtilTestClass::class . '::staticMethod', new \ReflectionMethod(ReflectionUtilTestClass::class, 'staticMethod')],
            'invokable class' => [new InvokableClass(), new \ReflectionMethod(InvokableClass::class, '__invoke')],
            'closure' => [$closure, new \ReflectionFunction($closure)],
        ];
    }
}

class ReflectionUtilTestClass
{
    public function method(string $string, ReflectionUtilTestClass $class): void
    {
    }

    public static function staticMethod(string $string, ReflectionUtilTestClass $class): void
    {
    }
}

class InvokableClass
{
    public function __invoke(): void
    {
    }
}
