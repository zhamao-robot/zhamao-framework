#!/usr/bin/env bash

function downloadIt() {
  downloader="wget"
  type wget >/dev/null 2>&1 || { downloader="curl"; }
  if [ "$downloader" = "wget" ]; then
    _down_prefix="O"
  else
    _down_prefix="o"
  fi
  _down_symbol=0
  if [ ! -f "$2" ]; then
    echo $1
    $downloader "$1" -$_down_prefix "$2" >/dev/null 2>&1 && \
    echo "完成！" && _down_symbol=1
  else
    echo "已存在！" && _down_symbol=1
  fi
  if [ $_down_symbol == 0 ]; then
    echo "失败！请检查网络连接！"
    rm -rf "$2"
    return 1
  fi
  return 0
}

downloadIt https://mirrors.cloud.tencent.com/openssl/source/ /tmp/openssl_ver.html

_php_ver="7.1.33"
_libiconv_ver="1.15"
_openssl_ver=$(cat /tmp/openssl_ver.html | grep "openssl-1.1.1.*.tar.gz<" | awk -F\" '{print $2}' | awk -F- '{print $2}' | awk -F".tar.gz" '{print $1}')
_swoole_ver="4.6.3"
_home_dir=$(pwd)"/"

function checkEnv() {
  echo -n "检测核心组件... "
  _msg="请通过包管理安装此依赖！"
  type git >/dev/null 2>&1 || { echo "失败，git 不存在！"$_msg; return 1; }
  type gcc >/dev/null 2>&1 || { echo "失败，gcc 不存在！"$_msg; return 1; }
  type g++ >/dev/null 2>&1 || { echo "失败，g++ 不存在！"$_msg; return 1; }
  type unzip >/dev/null 2>&1 || { echo "失败，unzip 不存在！"$_msg; return 1; }
  type autoconf >/dev/null 2>&1 || { echo "失败，autoconf 不存在！"; return 1; }
  #type pkg-config >/dev/null 2>&1 || { echo "失败，pkg-config 不存在！"$_msg; return 1; }
  type wget >/dev/null 2>&1 || type curl >/dev/null 2>&1 || { echo "失败，curl/wget 不存在！"$_msg; return 1; }
  echo "完成！"
  echo "如果下载过程中出现错误，请删除 runtime/ 文件夹重试！"
  echo "此脚本安装的php/swoole均为最小版本，不含其他扩展（如zip、xml、gd）等！"
  echo -n "如果编译过程缺少依赖，请通过包管理安装对应的依赖！[按回车继续] "
  # shellcheck disable=SC2034
  read ents
}



function downloadAll() {
  # 创建文件夹
  mkdir "$_home_dir""runtime" >/dev/null 2>&1
  mkdir "$_home_dir""runtime/tmp_download" >/dev/null 2>&1
  mkdir "$_home_dir""runtime/cellar" >/dev/null 2>&1
  _down_dir=$_home_dir"runtime/tmp_download/"

  # 下载PHP
  echo -n "正在下载 php 源码... "
  downloadIt "http://mirrors.sohu.com/php/php-$_php_ver.tar.gz" "$_down_dir""php.tar.gz" || { exit; } &

  # 下载libiconv
  echo -n "正在下载 libiconv 源码... "
  downloadIt "https://mirrors.tuna.tsinghua.edu.cn/gnu/libiconv/libiconv-$_libiconv_ver.tar.gz" "$_down_dir""libiconv.tar.gz" || { exit; } &

  echo -n "正在下载 openssl 源码... "
  downloadIt "http://mirrors.cloud.tencent.com/openssl/source/openssl-$_openssl_ver.tar.gz" "$_down_dir""openssl.tar.gz" || { exit; } &

  echo -n "正在下载 swoole 源码... "
  downloadIt "https://dl.zhamao.me/swoole/swoole-$_swoole_ver.tgz" "$_down_dir""swoole.tar.gz" || { exit; } &

  echo -n "正在下载 composer ... "
  downloadIt "https://mirrors.aliyun.com/composer/composer.phar" "$_home_dir""runtime/cellar/composer" || { exit; } &

  echo -n "正在下载 zlib ... "
  downloadIt "https://zlib.net/fossils/zlib-1.2.5.tar.gz" "$_down_dir""zlib.tar.gz" || { exit; } &

  echo -n "正在下载 libxml2 ... "
  downloadIt "https://dl.zhamao.me/libxml2/libxml2-2.9.8.tar.gz" "$_down_dir""libxml2.tar.gz" || { exit; } &

  echo -n "正在下载 libpng ... "
  downloadIt "https://dl.zhamao.me/libpng/libpng-1.6.37.tar.gz" "$_down_dir""libpng.tar.gz" || { exit; } &

  echo -n "正在下载 oniguruma ... "
  downloadIt "https://dl.zhamao.me/oniguruma/onig-6.9.6.tar.gz" "$_down_dir""onig.tar.gz" || { exit; } &

  wait

  #echo -n "正在下载 libcurl 源码... "
  #downloadIt "https://curl.se/download/curl-7.75.0.tar.gz" "$_down_dir""libcurl.tar.gz" || { exit; }
}

function compileIt() {
  _down_dir="$_home_dir""runtime/tmp_download/"
  _source_dir="$_home_dir""runtime/tmp_source/"
  _cellar_dir="$_home_dir""runtime/cellar/"
  case $1 in
  "onig")
    if [ -f "$_cellar_dir""onig/bin/onig-config" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""onig.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""onig-6.9.6" && \
    ./configure --prefix="$_cellar_dir""onig" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "libxml2")
    if [ -f "$_cellar_dir""libxml2/lib/libxml2.so" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""libxml2.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""libxml2-2.9.8" && \
    ./configure --prefix="$_cellar_dir""libxml2" --exec-prefix="$_cellar_dir""libxml2" --without-python && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "libpng")
    if [ -f "$_cellar_dir""libpng/bin/libpng-config" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""libpng.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""libpng-1.6.37" && \
    LDFLAGS="-L$_cellar_dir""zlib/lib" CPPFLAGS="-I$_cellar_dir""zlib/include" ./configure --prefix="$_cellar_dir""libpng" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "zlib")
    if [ -f "$_cellar_dir""zlib/lib/libz.so" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""zlib.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""zlib-1.2.5" && \
    ./configure --prefix="$_cellar_dir""zlib" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "libiconv")
    if [ -f "$_cellar_dir""libiconv/bin/iconv" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""libiconv.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""libiconv-"$_libiconv_ver && \
    ./configure --prefix="$_cellar_dir""libiconv" >/dev/null 2>&1 && \
    make -j4 >/dev/null 2>&1 && \
    make install >/dev/null 2>&1 && \
    echo "完成！"
    ;;
  "libzip")
    if [ -f "$_cellar_dir""libzip/bin/libzip" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""libzip.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""libzip-1.7.3" && \
    ./configure --prefix="$_cellar_dir""libzip" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "libcurl")
    if [ -f "$_cellar_dir""libcurl/bin/libcurl" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""libcurl.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""libcurl-7.75.0" && \
    ./configure --prefix="$_cellar_dir""libcurl" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "php")
    if [ -f "$_cellar_dir""php/bin/php" ]; then
      echo "已编译！" && return
    fi
    PKG_CONFIG_PATH="$_cellar_dir""openssl/lib/pkgconfig"
    PKG_CONFIG_PATH="$PKG_CONFIG_PATH:$_cellar_dir""zlib/lib/pkgconfig"
    PKG_CONFIG_PATH="$PKG_CONFIG_PATH:$_cellar_dir""libxml2/lib/pkgconfig"
    PKG_CONFIG_PATH="$PKG_CONFIG_PATH:$_cellar_dir""libpng/lib/pkgconfig"
    PKG_CONFIG_PATH="$PKG_CONFIG_PATH:$_cellar_dir""onig/lib/pkgconfig"
    tar -xf "$_down_dir""php.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""php-"$_php_ver && \
    ./buildconf --force && \
    PKG_CONFIG_PATH="$PKG_CONFIG_PATH" ./configure --prefix="$_cellar_dir""php" \
      --with-config-file-path="$_home_dir""runtime/etc" \
      --disable-fpm \
      --enable-cli \
      --enable-posix \
      --enable-ctype \
      --enable-mysqlnd \
      --enable-mbstring \
      --enable-gd \
      --enable-pdo \
      --enable-pcntl \
      --with-openssl="$_cellar_dir""openssl" \
      --enable-sockets \
      --without-sqlite3 \
      --without-pdo-sqlite \
      --with-pdo-mysql=mysqlnd \
      --with-zlib="$_cellar_dir""zlib" \
      --with-iconv="$_cellar_dir""libiconv" \
      --enable-phar && \
    make -j4 && \
    make install && \
    cp "$_source_dir""php-$_php_ver/php.ini-production" "$_home_dir""runtime/etc/php.ini" && \
    echo "完成！"
    ;;
  "openssl")
    if [ -f "$_cellar_dir""openssl/bin/openssl" ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""openssl.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""openssl-""$_openssl_ver" && \
    ./config --prefix="$_cellar_dir""openssl" && \
    make -j4 && \
    make install && \
    echo "完成！"
    ;;
  "swoole")
    "$_home_dir"runtime/cellar/php/bin/php --ri swoole >/dev/null 2>&1
    # shellcheck disable=SC2181
    if [ $? == 0 ]; then
      echo "已编译！" && return
    fi
    tar -xf "$_down_dir""swoole.tar.gz" -C "$_source_dir" && \
    cd "$_source_dir""swoole-""$_swoole_ver" && \
    PATH="$_cellar_dir""php/bin:$PATH" phpize && \
    PATH="$_cellar_dir""php/bin:$PATH" ./configure --prefix="$_cellar_dir""php" \
      --enable-sockets \
      --enable-http2 \
      --enable-openssl \
      --with-openssl-dir="$_cellar_dir""openssl" \
      --enable-mysqlnd && \
    make -j4 && \
    make install && \
    echo "extension=swoole.so" >> "$_home_dir""runtime/etc/php.ini" && \
    echo "完成！"
    ;;
  esac
}

function compileAll() {
  _down_dir=$_home_dir"runtime/tmp_download/"
  _source_dir=$_home_dir"runtime/tmp_source/"
  mkdir "$_source_dir" >/dev/null 2>&1
  mkdir "$_home_dir""runtime/etc" >/dev/null 2>&1

  echo -n "正在编译 libiconv ... "
  compileIt libiconv || { return 1; }

  #echo -n "正在编译 libcurl ... "
  #compileIt libcurl || { exit; }

  echo -n "正在编译 openssl ... "
  compileIt openssl || { return 1; }

  echo -n "正在编译 zlib ... "
  compileIt zlib || { return 1; }

  echo -n "正在编译 libxml2 ... "
  compileIt libxml2 || { return 1; }

  echo -n "正在编译 libpng ... "
  compileIt libpng || { return 1; }

  echo -n "正在编译 oniguruma ... "
  compileIt onig || { return 1; }

  #echo -n "正在编译 libzip ... "
  #compileIt libzip || { exit; }

  echo -n "正在编译 php ... "
  compileIt php || { return 1; }

  echo -n "正在编译 swoole ... "
  compileIt swoole || { return 1; }

  return 0
}

function linkBin(){
  mkdir "$_home_dir""runtime/bin" >/dev/null 2>&1
  ln -s "$_home_dir""runtime/cellar/php/bin/php" "$_home_dir""runtime/bin/php" >/dev/null 2>&1
  echo "runtime/cellar/php/bin/php runtime/cellar/composer \$@" > "$_home_dir""runtime/bin/composer" && chmod +x "$_home_dir""runtime/bin/composer"
  echo "Done!"
  "$_home_dir"runtime/bin/composer config repo.packagist composer https://mirrors.aliyun.com/composer/
}

checkEnv && \
downloadAll && \
compileAll && \
linkBin && \
echo "成功部署所有环境！" && \
echo -e "composer更新依赖：\t\"runtime/bin/composer update\"" && \
echo -e "启动框架（源码模式）：\t\"runtime/bin/php bin/start server\"" && \
echo -e "启动框架（普通模式）：\t\"runtime/bin/php vendor/bin/start server\""


