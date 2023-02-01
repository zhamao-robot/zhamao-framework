<?php

declare(strict_types=1);

namespace ZM\Store;

/**
 * Phar 打包、解包一系列工具集
 */
class PharHelper
{
    /**
     * 确认环境是否允许写入 phar
     *
     * 调用该方法将确认能否写入 Phar，如果不能但能通过 pcnlt_exec 自动切换为可写模式，则切换并继续执行后续流程。
     * 否则将抛出 \PharException 异常。
     *
     * @throws \PharException
     */
    public static function ensurePharWritable(): void
    {
        if (ini_get('phar.readonly') === '1') {
            if (!function_exists('pcntl_exec')) {
                throw new \PharException("Phar 处于只读模式，且 pcntl 扩展未加载，无法自动切换到读写模式。\n" . '请修改 php.ini 中的 phar.readonly 为 0，或执行 php -d phar.readonly=0 ' . $_SERVER['PHP_SELF'] . ' build');
            }
            // Windows 下无法使用 pcntl_exec
            if (DIRECTORY_SEPARATOR === '\\') {
                throw new \PharException("Phar 处于只读模式，且当前运行环境为 Windows，无法自动切换到读写模式。\n" . '请修改 php.ini 中的 phar.readonly 为 0，或执行 php -d phar.readonly=0 ' . $_SERVER['PHP_SELF'] . ' build');
            }
            if (ob_logger_registered()) {
                ob_logger()->info('Phar 处于只读模式，正在尝试切换到读写模式...');
            }
            sleep(1);
            $args = array_merge(['-d', 'phar.readonly=0'], $_SERVER['argv']);
            if (pcntl_exec(PHP_BINARY, $args) === false) {
                throw new \PharException('切换到读写模式失败，请检查环境。');
            }
        }
    }

    /**
     * 调用该方法将确认传入的 Phar 文件是否可写，如果不可写将抛出 \PharException 异常。
     *
     * @throws \PharException
     */
    public static function ensurePharFileWritable(string $phar_path): void
    {
        if (file_exists($phar_path) && !is_writable($phar_path)) {
            throw new \PharException('目标文件不可写：' . $phar_path);
        }
    }
}
