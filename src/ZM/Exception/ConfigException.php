<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class ConfigException extends ZMException
{
    public const UNSUPPORTED_FILE_TYPE = 'E00079';

    public const LOAD_CONFIG_FAILED = 'E00080';

    public function __construct($err_code, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode($err_code) . $message, $code, $previous);
    }

    public static function unsupportedFileType(string $file_path): ConfigException
    {
        return new self(self::UNSUPPORTED_FILE_TYPE, "不支持的配置文件类型：{$file_path}");
    }

    public static function loadConfigFailed(string $file_path, string $message): ConfigException
    {
        return new self(self::LOAD_CONFIG_FAILED, "加载配置文件失败：{$file_path}，{$message}");
    }
}
