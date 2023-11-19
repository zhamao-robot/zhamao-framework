#!/usr/bin/env bash

# 支持的环境变量：
#   ZM_DOWN_PHP_VERSION     php版本，默认为8.0
#   ZM_NO_LOCAL_PHP         如果填入任意内容，则不检查本地PHP，直接安装内建PHP（仅限Linux）
#   ZM_TEMP_DIR             脚本下载内建PHP和Composer的临时目录，默认为/tmp/.zm-runtime
#   ZM_CUSTOM_DIR           自定义新建目录名称，默认为zhamao-v3
#   ZM_COMPOSER_PACKAGIST   是否使用Composer的国外源，默认使用国内阿里云，如果设置并填写了内容，则自动使用Composer的国外源

ZM_PWD=$(pwd)
_cyan="\033[0;36m"
_reset="\033[0m"

# 彩头
function nhead() {
    if [ "$1" = "red" ]; then
        echo -ne "\033[0;31m[!]"
    elif [ "$1" = "yellow" ]; then
        echo -ne "\033[0;33m[?]"
    else
        echo -ne "\033[0;32m[*]"
    fi
    echo -e "\033[0m"
}

# 下载文件 $1 到目录 $2，自动选择使用 curl 或者 wget
function download_file() {
    downloader="wget"
    type wget >/dev/null 2>&1 || { downloader="curl"; }
    if [ "$downloader" = "wget" ]; then
        _down_prefix="O"
    else
        _down_prefix="o"
    fi
    _down_symbol=0
    if [ ! -f "$2" ]; then
        # 获取目录路径
        download_dir=$(dirname "$2")

        # 检查目录是否存在，如果不存在则创建
        if [ ! -d "$download_dir" ]; then
            mkdir -p "$download_dir"
        fi

        echo -ne "$(nhead) 正在下载 $1 到目录 $download_dir ... "
        $downloader "$1" -$_down_prefix "$2" >/dev/null 2>&1 && echo "完成！" && _down_symbol=1
    else
        echo "文件已存在！" && _down_symbol=1
    fi
    if [ $_down_symbol == 0 ]; then
        echo "$(nhead red) 下载失败！请检查网络连接！"
        rm -rf "$2"
        return 1
    fi
    return 0
}


# 安装下载内建PHP
function install_native_php() {
    ZM_PHP_VERSION="8.1"
    if [ "$ZM_DOWN_PHP_VERSION" != "" ]; then
        ZM_PHP_VERSION="$ZM_DOWN_PHP_VERSION"
    fi
    echo "$(nhead) 使用的内建 PHP 版本: $ZM_PHP_VERSION"

    rm -rf "$ZM_TEMP_DIR"
    mkdir "$ZM_TEMP_DIR" >/dev/null 2>&1
    if [ ! -f "$ZM_TEMP_DIR/php" ]; then
        download_file "https://dl.zhamao.xin/php-bin/down.php?php_ver=$ZM_PHP_VERSION&arch=$(uname -m)" "$ZM_TEMP_DIR/php.tgz" || return 1
        tar -xf "$ZM_TEMP_DIR/php.tgz" -C "$ZM_TEMP_DIR/" && rm -rf "$ZM_TEMP_DIR/php.tgz"
    fi
    echo "$(nhead) 安装内建 PHP 完成！"
    php_executable="$ZM_TEMP_DIR/php"
    return 0
}

# 安装下载Composer
function install_native_composer() {

    if [ ! -f "$ZM_TEMP_DIR/composer.phar" ]; then
        # 下载 composer.phar
        download_file "https://mirrors.aliyun.com/composer/composer.phar" "$ZM_TEMP_DIR/composer.phar" || return 1
        if [ "$php_executable" = "$ZM_TEMP_DIR/php" ]; then
            # shellcheck disable=SC2016
            txt='#!/usr/bin/env sh
if [ -f "$(dirname $0)/php" ]; then
    "$(dirname $0)/php" "$(dirname $0)/composer.phar" $*
else
    php "$(dirname $0)/composer.phar" $*
fi'
            echo "$txt" >"$ZM_TEMP_DIR/composer"
            chmod +x "$ZM_TEMP_DIR/composer"
        else
            mv "$ZM_TEMP_DIR/composer.phar" "$ZM_TEMP_DIR/composer" && chmod +x "$ZM_TEMP_DIR/composer"
        fi
    fi
    echo "$(nhead) 安装内建 Composer 完成！"
    composer_executable="$ZM_TEMP_DIR/composer"
    return 0
}

# 检查Composer可用性
function composer_check() {
    # 顺带检查一下
    echo "$(nhead) 正在检查 Git、unzip、7z 能否正常使用 ... "
    type git >/dev/null 2>&1 || {
        echo "$(nhead red) 检测到系统不存在 git 命令，可能无法正常使用 Composer 下载 GitHub 等仓库项目！"
    }
    zip_check_symbol=0
    if type unzip >/dev/null 2>&1; then
        zip_check_symbol=1
    fi
    if type 7z >/dev/null 2>&1; then
        zip_check_symbol=1
    fi
    if [ $zip_check_symbol -eq 0 ]; then
        if [ "$($php_executable -m | grep zip)" = "" ]; then
            echo "$(nhead red) 检测到系统不存在 unzip 或 7z 命令，PHP 不存在 zip 扩展，可能无法正常使用 Composer 下载压缩包项目！"
        fi
    fi

    # 测试 Composer 和 PHP 是否能正常使用
    if [ "$("$composer_executable" -n about | grep Manager)" = "" ]; then
        echo "$(nhead red) Download PHP binary and composer failed!"
        return 1
    fi
    echo "$(nhead) 环境检查完成！"
    return 0
}

# 环境检查
function darwin_env_check() {
    echo -ne "$(nhead) 检查是否存在 PHP ... "
    if type php >/dev/null 2>&1; then
        php_executable=$(which php)
        echo "位置：$php_executable"
    else
        echo "不存在"
        if type brew >/dev/null 2>&1; then
            echo -n "$(nhead yellow) 是否使用 Homebrew 安装 PHP？[y/N] "
            read -r y
            if [ "$y" = "" ]; then y="N"; fi
            if [ "$y" = "y" ]; then
                brew install php || echo "$(nhead red) 安装 PHP 失败！" && return 1
            else
                echo "$(nhead red) 跳过安装 PHP！" && return 1
            fi
        fi
    fi

    echo -ne "$(nhead) 检查是否存在 Composer ... "
    if type composer >/dev/null 2>&1; then
        composer_executable=$(which composer)
        echo "位置：$composer_executable"
    else
        echo "不存在，正在下载 Composer ..."
        install_native_composer || return 1
    fi

    composer_check || return 1
}

# 询问是否安装 native php
function prompt_install_native_php() {
    echo -ne "$(nhead yellow) 检测到系统的 PHP 不符合要求，是否下载安装独立的内建 PHP 和 Composer？[Y/n] "
    read -r y
    case $y in
    Y|y|"") return 0 ;;
    *) echo "$(nhead red) 跳过安装内建 PHP！" && return 1 ;;
    esac
}

# 环境检查
function linux_env_check() {
    if [ "$ZM_NO_LOCAL_PHP" != "" ]; then # 如果指定了不使用本地 php，则不检查，直接下载
        install_native_php && install_native_composer && composer_check && return 0 || return 1
    else
        echo -ne "$(nhead) 检查是否存在 PHP ... "
        if type php >/dev/null 2>&1; then
            php_executable=$(which php)
            echo "位置：$php_executable"
            ver_id=$($php_executable -r "echo PHP_VERSION_ID;")
            if [ "$ver_id" -lt 80000 ]; then
                echo "$(nhead red) PHP 版本过低，框架需要 PHP >= 8.0.0！" && \
                prompt_install_native_php && \
                install_native_php && \
                install_native_composer && composer_check && return 0 || return 1
            fi
            if [ "$($php_executable -m | grep tokenizer)" = "" ]; then
                echo "$(nhead red) PHP 不存在 tokenizer 扩展，可能无法正常使用框架！" && \
                prompt_install_native_php && \
                install_native_php && \
                install_native_composer && composer_check && return 0 || return 1
            fi
        else
            echo "不存在，将下载内建 PHP"
            install_native_php || return 1
        fi
    fi

    echo -ne "$(nhead) 检查是否存在 Composer ... "
    if type composer >/dev/null 2>&1; then
        composer_executable=$(which composer)
        echo "位置：$composer_executable"
    else
        echo "不存在，将下载内建 Composer"
        install_native_composer || return 1
    fi

    composer_check || return 1
}

function if_use_aliyun() {
    if [ "$ZM_COMPOSER_PACKAGIST" = "" ]; then
        $composer_executable -n config repos.packagist composer https://mirrors.aliyun.com/composer
    fi
}

function if_restore_native_runtime() {
    ZM_RUNTIME_DIR="$ZM_PWD/$ZM_CUSTOM_DIR/runtime/"
    mkdir -p "$ZM_RUNTIME_DIR"
    mkdir -p "$ZM_CUSTOM_DIR/runtime"
    if [ "$php_executable" = "$ZM_TEMP_DIR/php" ]; then
        echo "$(nhead) 移动内建 PHP 到框架目录 $ZM_RUNTIME_DIR ..." && \
            mv "$ZM_TEMP_DIR/php" "$ZM_RUNTIME_DIR" || {
                echo "$(nhead red) 移动内建 PHP 到框架目录失败！" && return 1
            }
        php_executable="$ZM_RUNTIME_DIR/php"
    fi
    if [ "$composer_executable" = "$ZM_TEMP_DIR/composer" ]; then
        echo "$(nhead) 移动内建 Composer 到框架目录 $ZM_RUNTIME_DIR ..." && \
            ([ -e "$ZM_TEMP_DIR/composer" ] && mv "$ZM_TEMP_DIR/composer" "$ZM_RUNTIME_DIR") || \
            ([ -e "$ZM_TEMP_DIR/composer.phar" ] && mv "$ZM_TEMP_DIR/composer.phar" "$ZM_RUNTIME_DIR") || {
                echo "$(nhead red) 移动内建 Composer 到框架目录失败！" && return 1
        }
        composer_executable="$ZM_RUNTIME_DIR/composer"
    fi
    return 0
}



function install_framework() {
    echo "$(nhead) 开始安装框架到目录 $ZM_CUSTOM_DIR ..."
    export COMPOSER_ALLOW_SUPERUSER=1
    mkdir -p "$ZM_PWD/$ZM_CUSTOM_DIR" && \
        cd "$ZM_PWD/$ZM_CUSTOM_DIR" && \
        $composer_executable init --name="zhamao/zhamao-v3-app" -n -q && \
        if_use_aliyun && \
        echo "$(nhead) 从 Composer 拉取框架 ..." && \
        echo '{"minimum-stability":"dev","prefer-stable":true}' > composer.json && $composer_executable require -n zhamao/framework:^3.0 && \
        $composer_executable require -n --dev swoole/ide-helper:^4.5 && \
        vendor/bin/zhamao init && \
        echo "$(nhead) 初始化框架脚手架文件 ..." && \
        $composer_executable dump-autoload -n && \
        if_restore_native_runtime && \
        show_success_msg || {
            echo "$(nhead red) 安装框架失败！" && cd $ZM_PWD && rm -rf "$ZM_CUSTOM_DIR" && return 1
        }
}

function show_success_msg() {
    echo -e "$(nhead) 框架安装成功，已安装到目录 $ZM_CUSTOM_DIR" && \
    echo -e "$(nhead) 进入应用目录：""$_cyan""cd $ZM_CUSTOM_DIR""$_reset" && \
    echo -e "$(nhead) 启动框架命令：""$_cyan""./zhamao server""$_reset" && \
    echo -e "$(nhead) 生成插件脚手架目录的命令：""$_cyan""./zhamao plugin:make""$_reset"
}

# 环境变量设置
test "$ZM_TEMP_DIR" = "" && ZM_TEMP_DIR="/tmp/.zm-runtime"
test "$ZM_CUSTOM_DIR" = "" && ZM_CUSTOM_DIR="zhamao-v3"

if [ -d "$ZM_PWD/$ZM_CUSTOM_DIR" ]; then
    echo "$(nhead red) 检测到目录 $ZM_CUSTOM_DIR/ 已安装过框架，请更换文件夹名称或删除旧文件夹再试！"
    exit 1
fi

# 检查系统环境，目前只支持 Linux
case $(uname -s) in
Linux) linux_env_check || exit 1 ;;
Darwin) darwin_env_check || exit 1 ;;
*) echo "$(nhead red) Only support Linux and macOS!" && exit 1 ;;
esac

# 安装框架
install_framework
