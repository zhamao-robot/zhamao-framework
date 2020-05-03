FROM phpswoole/swoole:4.4.15-php7.3
WORKDIR /app/
RUN echo "Asia/Shanghai" > /etc/timezone
#RUN dpkg-configure -f noninteractive tzdata
ENV LANG C.UTF_8

VOLUME ["/app/zhamao-framework/"]
ADD . /app/zhamao-framework
ADD . /app/zhamao-framework-bak
#RUN cd /app/zhamao-framework && composer update && composer clearcache
#RUN mv zhamao-framework-master zhamao-framework
WORKDIR /app/zhamao-framework

CMD ["/bin/bash", "-i", "/app/zhamao-framework-bak/.entry.sh"]
