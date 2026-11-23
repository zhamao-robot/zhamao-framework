<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use OneBot\Exception\ExceptionHandler;
use ZM\Config\RuntimePreferences;
use ZM\Exception\Handler;

class HandleExceptions implements Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void
    {
        // 注册全局错误处理器
        set_error_handler(function ($error_no, $error_msg, $error_file, $error_line) {
            $tips = [
                E_WARNING => ['PHP Warning: ', 'warning'],
                E_NOTICE => ['PHP Notice: ', 'notice'],
                E_USER_ERROR => ['PHP Error: ', 'error'],
                E_USER_WARNING => ['PHP Warning: ', 'warning'],
                E_USER_NOTICE => ['PHP Notice: ', 'notice'],
                E_RECOVERABLE_ERROR => ['PHP Recoverable Error: ', 'error'],
                E_DEPRECATED => ['PHP Deprecated: ', 'notice'],
                E_USER_DEPRECATED => ['PHP User Deprecated: ', 'notice'],
            ];
            $level_tip = $tips[$error_no] ?? ['PHP Unknown: ', 'error'];
            $error = $level_tip[0] . $error_msg . ' in ' . $error_file . ' on ' . $error_line;

            // PHP 8.4 起，第三方依赖（vendor 内）会产生大量 deprecated 告警（如「隐式可空参数」），
            // 这些代码框架和使用者都无法直接修改，按 notice 记录只会刷屏。这里降级为 debug：
            // 默认日志级别看不到，需要排查时用 -d（debug 模式）或 -l debug 仍然可以查看。
            if (self::isVendorDeprecation($error_no, $error_file)) {
                logger()->debug($error);
                return true;
            }

            logger()->{$level_tip[1]}($error);
            // 如果 return false 则错误会继续递交给 PHP 标准错误处理
            return true;
        });

        // 重载异常处理器
        ExceptionHandler::getInstance()->overrideWith(new Handler());
    }

    /**
     * 判断该告警是否为 vendor 目录下第三方依赖产生的弃用告警
     */
    private static function isVendorDeprecation(int $error_no, string $error_file): bool
    {
        return in_array($error_no, [E_DEPRECATED, E_USER_DEPRECATED], true)
            && str_contains(str_replace('\\', '/', $error_file), '/vendor/');
    }
}
