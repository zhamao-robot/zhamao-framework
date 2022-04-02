<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use Throwable;
use ZM\Annotation\CQ\CQCommand;
use ZM\API\CQ;
use ZM\Event\EventManager;
use ZM\Utils\DataProvider;
use ZM\Utils\MessageUtil;

/**
 * @internal
 */
class MessageUtilTest extends TestCase
{
    public function testAddShortCommand(): void
    {
        EventManager::$events[CQCommand::class] = [];
        MessageUtil::addShortCommand('test', 'test');
        $this->assertCount(1, EventManager::$events[CQCommand::class]);
    }

    /**
     * @dataProvider providerTestContainsImage
     */
    public function testContainsImage(string $msg, bool $expected): void
    {
        $this->assertEquals($expected, MessageUtil::containsImage($msg));
    }

    public function providerTestContainsImage(): array
    {
        return [
            'empty' => ['', false],
            'text only' => ['hello world', false],
            'image only' => ['[CQ:image,file=123456.jpg]', true],
            'image' => ['hello world![CQ:image,file=123456.jpg]', true],
            'two image' => ['hello world![CQ:image,file=123456.jpg][CQ:image,file=123456.jpg]', true],
            // 'malformed image' => ['[CQ:image,file=]', false],
        ];
    }

    public function testGenerateCommandHelp(): void
    {
        EventManager::$events[CQCommand::class] = [];
        $cmd = new CQCommand('测试命令');
        $cmd->class = self::class;
        $cmd->method = __FUNCTION__;
        EventManager::addEvent(CQCommand::class, $cmd);
        $help = MessageUtil::generateCommandHelp();
        $this->assertEquals('测试命令：无描述', $help[0]);
    }

    /**
     * @dataProvider providerTestArrayToStr
     */
    public function testArrayToStr(array $array, string $expected): void
    {
        $this->assertEquals($expected, MessageUtil::arrayToStr($array));
    }

    public function providerTestArrayToStr(): array
    {
        $tmp = $this->providerTestStrToArray();
        $result = [];
        foreach ($tmp as $desc => $case) {
            $result[$desc] = [$case[1], $case[0]];
        }
        return $result;
    }

    public function testMatchCommand(): void
    {
        // 这里理论上需要覆盖所有条件，但先暂时这样好了
        EventManager::$events[CQCommand::class] = [
            new CQCommand('测试命令'),
        ];
        $this->assertEquals(true, MessageUtil::matchCommand('测试命令', [
            'user_id' => '123456',
            'group_id' => '123456',
            'message_type' => 'group',
        ])->status);
    }

    /**
     * @dataProvider providerTestIsAtMe
     */
    public function testIsAtMe(string $msg, bool $expected): void
    {
        $this->assertEquals($expected, MessageUtil::isAtMe($msg, 123456789));
    }

    public function providerTestIsAtMe(): array
    {
        return [
            'me only' => ['[CQ:at,qq=123456789]', true],
            'empty qq' => ['[CQ:at,qq=]', false],
            'message behind' => ['[CQ:at,qq=123456789] hello', true],
            'message front' => ['hello [CQ:at,qq=123456789]', true],
            'message surround' => ['hello [CQ:at,qq=123456789] world', true],
            'not at me' => ['hello world', false],
            'other' => ['[CQ:at,qq=123456789] hello [CQ:at,qq=987654321]', true],
            'other only' => ['[CQ:at,qq=987654321]', false],
        ];
    }

    public function testGetImageCQFromLocal(): void
    {
        file_put_contents('/tmp/test.jpg', 'test');
        $this->assertEquals('[CQ:image,file=base64://' . base64_encode('test') . ']', MessageUtil::getImageCQFromLocal('/tmp/test.jpg'));
        unlink('/tmp/test.jpg');
    }

    /**
     * @dataProvider providerTestStrToArray
     */
    public function testStrToArray(string $str, array $expected): void
    {
        $this->assertEquals($expected, MessageUtil::strToArray($str));
    }

    public function providerTestStrToArray(): array
    {
        $text = static function ($str): array {
            return ['type' => 'text', 'data' => ['text' => $str]];
        };

        return [
            'empty string' => ['', []],
            'pure string' => ['foobar', [$text('foobar')]],
            'spaced string' => ['hello world', [$text('hello world')]],
            'spaced and multiline string' => ["hello\n  world", [$text("hello\n  world")]],
            'string containing CQ' => ['[CQ:at,qq=123456789]', [['type' => 'at', 'data' => ['qq' => '123456789']]]],
        ];
    }

    /**
     * @dataProvider providerTestSplitCommand
     */
    public function testSplitCommand(string $msg, array $expected): void
    {
        $this->assertEquals($expected, MessageUtil::splitCommand($msg));
    }

    public function providerTestSplitCommand(): array
    {
        return [
            'empty' => ['', ['']],
            'spaced' => ['hello world', ['hello', 'world']],
            'multiline' => ["hello\nworld", ['hello', 'world']],
            'many spaces' => ['hello    world', ['hello', 'world']],
            'many spaces and multiline' => ["hello\n    world", ['hello', 'world']],
            'many parts' => ['hello world foo bar', ['hello', 'world', 'foo', 'bar']],
        ];
    }

    /**
     * @throws Throwable
     */
    public function testDownloadCQImage(): void
    {
        if (file_exists(DataProvider::getDataFolder('images') . '/test.jpg')) {
            unlink(DataProvider::getDataFolder('images') . '/test.jpg');
        }
        $msg = '[CQ:image,file=test.jpg,url=https://zhamao.xin/file/hello.jpg]';

        try {
            $result = MessageUtil::downloadCQImage($msg);
            $this->assertIsArray($result);
            $this->assertCount(1, $result);
            $this->assertFileExists(DataProvider::getDataFolder('images') . '/test.jpg');
            unlink(DataProvider::getDataFolder('images') . '/test.jpg');
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'enable-openssl') !== false) {
                $this->markTestSkipped('OpenSSL is not enabled');
            }
            throw $e;
        }
    }
}
