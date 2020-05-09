FROM ubuntu:18.04
WORKDIR /app/
RUN echo "Asia/Shanghai" > /etc/timezone
ENV LANG C.UTF_8
ENV LC_ALL C.UTF-8
ENV LANGUAGE C.UTF-8

RUN apt-get update && apt-get install -y software-properties-common tzdata
RUN dpkg-reconfigure -f noninteractive tzdata
VOLUME ["/app/zhamao-framework/"]
RUN add-apt-repository ppa:ondrej/php && \
	apt-get update && \
	apt-get install php php-dev php-mbstring gcc make openssl \
		php-mbstring php-json php-curl php-mysql -y && \
	apt-get install wget composer -y && \
	wget https://github.com/swoole/swoole-src/archive/v4.5.0.tar.gz && \
	tar -zxvf v4.5.0.tar.gz && \
	cd swoole-src-4.5.0/ && \
	phpize && ./configure --enable-openssl --enable-mysqlnd && make -j2 && make install && \
	(echo "extension=swoole.so" >> $(php -i | grep "Loaded Configuration File" | awk '{print $5}'))


ADD . /app/zhamao-framework
ADD . /app/zhamao-framework-bak
#RUN cd /app/zhamao-framework && composer update && composer clearcache
#RUN mv zhamao-framework-master zhamao-framework
WORKDIR /app/zhamao-framework

CMD ["/bin/bash", "-i", "/app/zhamao-framework-bak/.entry.sh"]
