<?php

declare(strict_types=1);

namespace ZM\Utils;

class ZMUtil
{
    /**
     * 获取 composer.json 并转为数组进行读取使用
     */
    public static function getComposerMetadata(): ?array
    {
        return json_decode(file_get_contents(SOURCE_ROOT_DIR . '/composer.json'), true);
    }
}
