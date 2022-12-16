FROM alpine:latest

MAINTAINER sunxyw <me@sunxyw.xyz>

RUN apk add --no-cache curl && \
    mkdir -p /bot && \
    curl -L -o /bot/walle-q https://github.com/onebot-walle/walle-q/releases/latest/download/walle-q-i686-linux-musl && \
    chmod +x /bot/walle-q

ENV TZ=Asia/Shanghai

ENTRYPOINT [ "/bot/walle-q" ]
