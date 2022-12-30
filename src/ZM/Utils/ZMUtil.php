<?php

declare(strict_types=1);

namespace ZM\Utils;

class ZMUtil
{
    /**
     * 获取 composer.json 并转为数组进行读取使用
     *
     * @param  null|string    $path 路径
     * @throws \JsonException
     */
    public static function getComposerMetadata(?string $path = null): ?array
    {
        return json_decode(file_get_contents(($path ?? SOURCE_ROOT_DIR) . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
