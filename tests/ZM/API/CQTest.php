<?php

declare(strict_types=1);

namespace Tests\ZM\API;

use PHPUnit\Framework\TestCase;
use ZM\API\CQ;

/**
 * @internal
 */
class CQTest extends TestCase
{
    /**
     * @dataProvider providerShare
     * @param mixed $url
     * @param mixed $title
     * @param mixed $content
     * @param mixed $image
     * @param mixed $expected
     */
    public function testShare($url, $title, $content, $image, $expected)
    {
        $this->assertEquals($expected, CQ::share($url, $title, $content, $image));
    }

    public function providerShare(): array
    {
        return [
            'all' => [
                'https://www.baidu.com',
                'hello',
                'world',
                'https://www.baidu.com/img/bd_logo1.png',
                '[CQ:share,url=https://www.baidu.com,title=hello,content=world,image=https://www.baidu.com/img/bd_logo1.png]',
            ],
            'url title' => [
                'https://www.baidu.com',
                '123',
                null,
                null,
                '[CQ:share,url=https://www.baidu.com,title=123]',
            ],
            'url title content' => [
                'https://www.baidu.com',
                '123',
                '456',
                null,
                '[CQ:share,url=https://www.baidu.com,title=123,content=456]',
            ],
        ];
    }

    public function testShake()
    {
        $this->assertEquals('[CQ:shake]', CQ::shake());
    }

    public function testLocation()
    {
        $this->assertEquals('[CQ:location,lat=23.137466,lon=113.352425]', CQ::location(23.137466, 113.352425));
    }

    public function testVideo()
    {
        $this->assertEquals('[CQ:video,file=https://www.baidu.com,cache=0,proxy=false,timeout=20]', CQ::video('https://www.baidu.com', false, false, 20));
    }

    public function testContact()
    {
        $this->assertEquals('[CQ:contact,type=qq,id=123456789]', CQ::contact('qq', '123456789'));
    }

    public function testForward()
    {
        $this->assertEquals('[CQ:forward,id=123456789]', CQ::forward(123456789));
    }

    public function testAnonymous()
    {
        $this->assertEquals('[CQ:anonymous,ignore=0]', CQ::anonymous(0));
    }

    public function testCustom()
    {
        $this->assertEquals('[CQ:test,type=test,data=hello]', CQ::_custom('test', ['type' => 'test', 'data' => 'hello']));
    }

    public function testEscape()
    {
        $this->assertEquals('hello&#91;&#93;,', CQ::escape('hello[],'));
    }

    /**
     * @dataProvider providerMusic
     * @param mixed $data
     * @param mixed $expected
     */
    public function testMusic($data, $expected)
    {
        $this->assertEquals($expected, CQ::music(...$data));
    }

    public function providerMusic(): array
    {
        return [
            'music qq' => [
                ['qq', '123456789'],
                '[CQ:music,type=qq,id=123456789]',
            ],
            'music 163' => [
                ['163', '123456789'],
                '[CQ:music,type=163,id=123456789]',
            ],
            'music xiami' => [
                ['xiami', '123456789'],
                '[CQ:music,type=xiami,id=123456789]',
            ],
            'music custom' => [
                ['custom', '123456789'],
                ' ',
            ],
            'music custom url audio title' => [
                ['custom', '123456789', 'test', 'test1'],
                '[CQ:music,type=custom,url=123456789,audio=test,title=test1]',
            ],
            'music custom url audio title content' => [
                ['custom', '123456789', 'test', 'test1', 'test2'],
                '[CQ:music,type=custom,url=123456789,audio=test,title=test1,content=test2]',
            ],
            'music custom url audio title content image' => [
                ['custom', '123456789', 'test', 'test1', 'test2', 'test3'],
                '[CQ:music,type=custom,url=123456789,audio=test,title=test1,content=test2,image=test3]',
            ],
            'music custom test' => [
                ['custom test', '123456789', 'test', 'test1', 'test2', 'test3'],
                ' ',
            ],
        ];
    }

    public function testPoke()
    {
        $this->assertEquals('[CQ:poke,type=id,id=123456789]', CQ::poke('id', '123456789'));
    }

    public function testJson()
    {
        $this->assertEquals('[CQ:json,data={"a":"b&#91;"},resid=1]', CQ::json(json_encode(['a' => 'b[']), 1));
    }

    public function testEncode()
    {
        $this->assertEquals('hello&#91;&#93;,', CQ::encode('hello[],'));
    }

    public function testDice()
    {
        $this->assertEquals('[CQ:dice]', CQ::dice());
    }

    public function testRecord()
    {
        $this->assertEquals('[CQ:record,file=https://www.baidu.com,cache=0]', CQ::record('https://www.baidu.com', false, false));
    }

    public function testDecode()
    {
        $this->assertEquals('hello[],', CQ::decode('hello&#91;&#93;,'));
    }

    /**
     * @dataProvider providerRemoveCQ
     * @param mixed $msg
     * @param mixed $expected
     */
    public function testRemoveCQ($msg, $expected)
    {
        $this->assertEquals($expected, CQ::removeCQ($msg));
    }

    public function providerRemoveCQ(): array
    {
        return [
            'remove cq 1' => ['hello[CQ:at,qq=123456789],', 'hello,'],
            'remove cq 2' => ['hello[CQ:at,qq=123456789,', 'hello[CQ:at,qq=123456789,'],
            'remove cq 3' => ['[CQ:dice]hello[CQ:at,qq=123456789]', 'hello'],
        ];
    }

    public function testGetAllCQ()
    {
        $array = CQ::getAllCQ('[CQ:dice][CQ:at,qq=123456789]');
        $this->assertEquals([
            [
                'type' => 'dice',
                'start' => 0,
                'end' => 8,
            ],
            [
                'type' => 'at',
                'params' => [
                    'qq' => '123456789',
                ],
                'start' => 9,
                'end' => 28,
            ],
        ], $array);
    }

    /**
     * @dataProvider providerGetCQ
     * @param mixed $cq
     * @param mixed $expected
     */
    public function testGetCQ($cq, $expected)
    {
        $this->assertEquals($expected, CQ::getCQ($cq));
    }

    public function providerGetCQ(): array
    {
        return [
            'one dice' => ['[CQ:dice]', [
                'type' => 'dice',
                'start' => 0,
                'end' => 8,
            ]],
            'one at' => ['[CQ:at,qq=123456789]', [
                'type' => 'at',
                'params' => [
                    'qq' => '123456789',
                ],
                'start' => 0,
                'end' => 19,
            ]],
            'invalid cq 1' => ['[CQ:at,qq=123456789', null],
            'invalid cq 2' => ['[CQ;at,qq=123456789]', null],
        ];
    }

    public function testImage()
    {
        $this->assertEquals('[CQ:image,file=https://www.baidu.com]', CQ::image('https://www.baidu.com'));
    }

    public function testRps()
    {
        $this->assertEquals('[CQ:rps]', CQ::rps());
    }

    public function testReplace()
    {
        $this->assertEquals('[]', CQ::replace('{{}}'));
    }

    public function testAt()
    {
        $this->assertEquals('[CQ:at,qq=123456789]', CQ::at('123456789'));
        $this->assertEquals(' ', CQ::at(null));
    }

    public function testXml()
    {
        $this->assertEquals('[CQ:xml,data=<xml></xml>]', CQ::xml('<xml></xml>'));
    }

    public function testFace()
    {
        $this->assertEquals('[CQ:face,id=1]', CQ::face(1));
        $this->assertEquals(' ', CQ::face(null));
    }

    public function testNode()
    {
        $this->assertEquals('[CQ:node,user_id=test,nickname=content,content=blah]', CQ::node('test', 'content', 'blah'));
    }
}
