#!/bin/bash

if [[ ! -d "coolq-data" ]]; then
  mkdir coolq-data
fi
sudo docker start coolq >/dev/null 2>&1

if [[ ! $? -eq 0 ]]; then
    echo -n "请输入你的VNC登陆密码(无回显): "
    read -s vnc_pwd
    echo -n "请输入你的反向ws连接地址(默认ws://127.0.0.1:20000/): "
    read reverse_url
    if [[ ${reverse_url} = "" ]]; then
        reverse_url="ws://127.0.0.1:20000/"
        echo "使用默认ws地址。"
    fi
    echo -n "请输入连接CQBot-swoole框架的token（没有请回车,无回显）："
    read access_token
    while :
    do
        echo -n "请输入你的酷Q下载版本 [1(CQA,默认) / 2(CQP)] : "
        read cqp_ver
        if [[ ${cqp_ver} = "" ]]; then
            cqp_ver="1"
        fi
        link="http://dlsec.cqp.me/cqa-tuling"
        if [[ ${cqp_ver} = "2" ]]; then
            link="-e COOLQ_URL=http://dlsec.cqp.me/cqp-tuling"
            break
        elif [[ ${cqp_ver} = "1" ]]; then
            link=""
            break
        else
            echo "你输入的数字有误！"
            continue
        fi
    done
    if [[ ${access_token} = "" ]]; then
      access_token2=" "
    else 
      access_token2="-e CQHTTP_ACCESS_TOKEN="${access_token}
    fi
    host_mode_line="--net=host"
    sudo docker run --name coolq -d -v $(pwd)/coolq-data:/home/user/coolq \
    ${host_mode_line} \
    -e VNC_PASSWD=${vnc_pwd} \
    -e CQHTTP_USE_WS_REVERSE=true \
    ${link} \
    ${access_token2} \
    -e CQHTTP_WS_REVERSE_USE_UNIVERSAL_CLIENT=true \
    -e CQHTTP_WS_REVERSE_URL=${reverse_url} \
    -e FORCE_ENV=false \
    richardchien/cqhttp:latest
    echo -n "成功启动docker！正在等待酷Q下载完成... "
    while [[ ! -f "coolq-data/conf/CQP.cfg" ]]
    do
        sleep 1s
    done
    echo ""
    echo "下载完成，请登陆VNC进行登陆QQ！"
else
    sudo docker start coolq
    echo "已启动酷Q docker！"
fi
