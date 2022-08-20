<?php

namespace Tests\ZM\Utils;

use ZM\Utils\ZMUtil;

class ZMUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testComposer()
    {
        $this->assertEquals('zhamao/framework', ZMUtil::getComposerMetadata()['name']);
    }
}
