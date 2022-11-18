<?php

declare(strict_types=1);

namespace ZM\Exception;

class ConfigException extends ZMException
{
    public const UNSUPPORTED_FILE_TYPE = 79;

    public const LOAD_CONFIG_FAILED = 80;

    public static function unsupportedFileType(string $file_path): ConfigException
    {
        return new self("不支持的配置文件类型：{$file_path}", '请检查配置文件的后缀名是否正确', self::UNSUPPORTED_FILE_TYPE);
    }

    public static function loadConfigFailed(string $file_path, string $message): ConfigException
    {
        return new self("加载配置文件失败：{$file_path}，{$message}", '请检查配置文件的格式是否正确，并尝试按照错误信息排查', self::LOAD_CONFIG_FAILED);
    }
}
