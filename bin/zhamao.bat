@echo off
@REM Check if ZM_CUSTOM_PHP_PATH is set
IF /i "%ZM_CUSTOM_PHP_PATH%" neq "" (
    @REM Set the path to the custom PHP
    echo "* Using custom PHP executable: %ZM_CUSTOM_PHP_PATH%"
    SET executable=%ZM_CUSTOM_PHP_PATH%
) ELSE IF exist ./runtime/php.exe (
    @REM Set the path to the built-in PHP
    echo "* Using built-in PHP executable"
    SET executable=.\runtime\php.exe
) ELSE (
    @REM Set the path to the system PHP
    echo "* Using system PHP executable"
    SET executable=php
)

IF exist src/entry.php (
    @REM Run the PHP entry point
    %executable% src/entry.php %*
) ELSE IF exist vendor/zhamao/framework/src/entry.php (
    @REM Run the PHP entry point
    %executable% vendor/zhamao/framework/src/entry.php %*
) ELSE (
    @REM No entry point found
    echo "[ErrCode:E00015] Cannot find zhamao-framework entry file!"
    exit /b 1
)
