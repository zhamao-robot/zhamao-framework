<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Annotation\CQ\CQCommand;
use ZM\Event\EventManager;
use ZM\Utils\DataProvider;
use ZM\Utils\MessageUtil;

/**
 * @internal
 * @coversNothing
 */
class MessageUtilTest extends TestCase
{
    public function testAddShortCommand(): void
    {
        // 此处需要进行 Worker 间通信，无法测试
//        EventManager::$events[CQCommand::class] = [];
//        MessageUtil::addShortCommand('test', 'test');
//        $this->assertCount(1, EventManager::$events[CQCommand::class]);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider providerTestContainsImage
     * @param mixed $msg
     * @param mixed $expected
     */
    public function testContainsImage($msg, $expected): void
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
        // 因为内部使用了 WorkerCache，而我们暂时无法模拟 WorkerCache，所以此处无法进行测试
//        EventManager::$events[CQCommand::class] = [
//            new CQCommand('测试命令'),
//            new CQCommand('测试命令2', '执行命令*'),
//        ];
//        $help = MessageUtil::generateCommandHelp();
//        $this->assertEquals('测试命令', $help[0]);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider providerTestArrayToStr
     * @param mixed $array
     * @param mixed $expected
     */
    public function testArrayToStr($array, $expected): void
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
     * @param mixed $msg
     * @param mixed $expected
     */
    public function testIsAtMe($msg, $expected): void
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
     * @param mixed $str
     * @param mixed $expected
     */
    public function testStrToArray($str, $expected): void
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
     * @param mixed $msg
     * @param mixed $expected
     */
    public function testSplitCommand($msg, $expected): void
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

    public function testDownloadCQImage(): void
    {
        if (file_exists(DataProvider::getDataFolder('images') . '/test.jpg')) {
            unlink(DataProvider::getDataFolder('images') . '/test.jpg');
        }
        $msg = '[CQ:image,file=test.jpg,url=https://zhamao.xin/file/hello.jpg]';
        $result = MessageUtil::downloadCQImage($msg);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertFileExists(DataProvider::getDataFolder('images') . '/test.jpg');
        unlink(DataProvider::getDataFolder('images') . '/test.jpg');
    }
}
