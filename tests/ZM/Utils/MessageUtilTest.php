<?php

namespace ZM\Utils;

use PHPUnit\Framework\TestCase;
use Swoole\WebSocket\Frame;
use ZM\Requests\ZMRequest;

class MessageUtilTest extends TestCase
{
    public function setUp(): void {
        ZMRequest::websocket();
        $a = new Frame();

        $a->opcode = WEBSOCKET_OPCODE_PONG;
    }

    public function testGetImageCQFromLocal() {
        file_put_contents("/tmp/a.jpg", "fake photo");
        $this->assertEquals(
            MessageUtil::getImageCQFromLocal("/tmp/a.jpg"),
            "[CQ:image,file=base64://".base64_encode("fake photo")."]"
        );
    }

    public function testSplitCommand() {
        $msg_sample_1 = "你好啊     233\n\nhello";
        $msg_sample_2 = "";
        $this->assertCount(3, MessageUtil::splitCommand($msg_sample_1));
        $this->assertCount(1, MessageUtil::splitCommand($msg_sample_2));
    }

    public function testIsAtMe() {
        $this->assertTrue(MessageUtil::isAtMe("[CQ:at,qq=123]", 123));
        $this->assertFalse(MessageUtil::isAtMe("[CQ:at,qq=]", 0));
    }

    public function testDownloadCQImage() {
        if (file_exists(WORKING_DIR."/zm_data/images/abc.jpg"))
            unlink(WORKING_DIR."/zm_data/images/abc.jpg");
        ob_start();
        $msg = "[CQ:image,file=abc.jpg,url=https://zhamao.xin/file/hello.jpg]";
        $result = MessageUtil::downloadCQImage($msg, "/home/jerry/fweewfwwef/wef");
        $this->assertFalse($result);
        $this->assertStringContainsString("E00059", ob_get_clean());
        $result = MessageUtil::downloadCQImage($msg);
        $this->assertIsArray($result);
        $this->assertFileExists(WORKING_DIR."/zm_data/images/abc.jpg");
        $result = MessageUtil::downloadCQImage($msg.$msg);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testContainsImage() {
        $msg_sample = "hello\n[CQ:imag2]";
        $this->assertFalse(MessageUtil::containsImage($msg_sample));
        $this->assertTrue(MessageUtil::containsImage($msg_sample."[CQ:image,file=123]"));
    }

    public function testMatchCommand() {

    }
}
