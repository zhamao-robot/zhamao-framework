FROM phpswoole/swoole:5.0-php8.1

MAINTAINER sunxyw <me@sunxyw.xyz>

WORKDIR "/bot"

ENV TZ=Asia/Shanghai

EXPOSE 20001-20005

RUN docker-php-ext-install pcntl

ENTRYPOINT [ "./zhamao" ]
CMD [ "server" ]
