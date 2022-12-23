<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use Tests\TestCase;
use ZM\Utils\ZMUtil;

/**
 * @internal
 */
class ZMUtilTest extends TestCase
{
    public function testComposer()
    {
        $this->assertEquals('zhamao/framework', ZMUtil::getComposerMetadata()['name']);
    }
}
