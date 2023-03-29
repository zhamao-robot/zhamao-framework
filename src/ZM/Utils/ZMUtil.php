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

    /**
     * 写入 composer.json
     *
     * @param null|string $path    路径
     * @param array       $content 内容数组
     */
    public static function putComposerMetadata(?string $path = null, array $content = []): false|int
    {
        return file_put_contents(($path ?? SOURCE_ROOT_DIR) . '/composer.json', json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取当前 Composer 二进制执行命令的初始位置
     */
    public static function getComposerExecutable(): string
    {
        $env = getenv('COMPOSER_EXECUTABLE');
        if ($env === false) {
            $env = 'composer';
        }
        return $env;
    }
}
