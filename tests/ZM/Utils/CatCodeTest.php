<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use OneBot\V12\Object\MessageSegment;
use Tests\TestCase;
use ZM\Utils\CatCode;

/**
 * @internal
 */
class CatCodeTest extends TestCase
{
    /**
     * @dataProvider provideTestConvertFromSegment
     */
    public function testConvertFromSegment(mixed $segment, string $expected): void
    {
        $this->assertSame($expected, CatCode::fromSegment($segment));
    }

    public function provideTestConvertFromSegment(): array
    {
        return [
            'string' => ['[CatCode:mention,user_id=123456789]', '[CatCode:mention,user_id=123456789]'],
            'segment instance' => [new MessageSegment('mention', ['user_id' => '123456789']), '[CatCode:mention,user_id=123456789]'],
            'multiple segment instance' => [
                [
                    new MessageSegment('mention', ['user_id' => '123456789']),
                    new MessageSegment('text', ['text' => 'Hello']),
                ],
                '[CatCode:mention,user_id=123456789]Hello',
            ],
            'array contains non-segment' => [
                [
                    new MessageSegment('mention', ['user_id' => '123456789']),
                    'Hello',
                ],
                '',
            ],
            'non-string, non-segment, non-array' => [123, ''],
        ];
    }

    /**
     * @dataProvider provideTestEscapeText
     */
    public function testEscapeText(string $text): void
    {
        $encoded = CatCode::encode($text);
        $decoded = CatCode::decode($encoded);
        $this->assertSame($text, $decoded);
    }

    public function provideTestEscapeText(): array
    {
        return [
            ["前缀是'['后缀是']', 还有以及一个特殊的&"],
            ["[前缀是'['后缀是']', 还有以及一个特殊的&"],
            ["前缀是'['后缀是']', 还有以及一个特殊的"],
            ["&前缀是'['后缀是']', 还有以及一个特殊的"],
            ["&前缀是'['后缀是']', 还有以及一个特殊的]"],
        ];
    }

    public function testEscapeTextForContent(): void
    {
        $text = "前缀是'['后缀是']', 还有以及一个特殊的&";
        $encoded = CatCode::encode($text, true);
        $decoded = CatCode::decode($encoded, true);
        $this->assertSame($text, $decoded);
    }
}
