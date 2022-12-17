FROM alpine:latest

MAINTAINER sunxyw <me@sunxyw.xyz>

RUN apk add --no-cache curl
RUN mkdir -p /bot && \
    curl -fsSL https://github.com/onebot-walle/walle-q/releases/latest/download/walle-q-i686-linux-musl.tar.gz -O && \
    tar -zxvf walle-q-i686-linux-musl.tar.gz -C /bot && \
    rm -rf walle-q-i686-linux-musl.tar.gz && \
    chmod +x /bot/walle-q-i686-linux-musl

ENV TZ=Asia/Shanghai

ENTRYPOINT [ "/bot/walle-q-i686-linux-musl" ]
