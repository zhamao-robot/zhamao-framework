#!/bin/bash

echo "正在检查最新版本的go-cqhttp..."

if [ "$(uname -m)" = "x86_64" ]; then
    arch_type="amd64"
elif [ "$(uname -m)" = "i386" ]; then
    arch_type="386"
elif [ "$(uname -m)" = "aarch64" ]; then
    arch_type="arm64"
else
    echo "Not supported architecture: $(uname -m)"
    exit 1
fi

aas=$(uname -s | tr 'A-Z' 'a-z')

ver=$(wget -qO- -t1 -T2 "https://fgit-api.zhamao.me/repos/Mrs4s/go-cqhttp/releases" | grep "tag_name" | head -n 1 | awk -F ":" '{print $2}' | sed 's/\"//g;s/,//g;s/ //g')

if [ "$ver" != "" ]; then
    echo "最新版本："$ver
    echo -n "是否下载到本地？[y/N] "
    read option
    if [ "$option" = "y" ]; then
        wget https://fgit.zhamao.me/Mrs4s/go-cqhttp/releases/download/$ver/go-cqhttp-$ver-$aas-$arch_type.tar.gz -O temp.tar.gz
        if [ $? != 0 ]; then
            wget https://fgit.zhamao.me/Mrs4s/go-cqhttp/releases/download/$ver/go-cqhttp_"$aas""_""$arch_type"".tar.gz" -O temp.tar.gz
        fi
        tar -zxvf temp.tar.gz go-cqhttp
        rm temp.tar.gz
        echo "下载完成，启动命令：./go-cqhttp"
        echo "首次启动后先编辑config.hjson文件！"
    fi
fi
