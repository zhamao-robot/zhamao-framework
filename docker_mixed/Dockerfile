FROM richardchien/cqhttp:latest
RUN apt-get update && apt-get install -y software-properties-common && \
	add-apt-repository ppa:ondrej/php && \
	apt-get update && \
	apt-get install php7.3 php7.3-dev php7.3-mbstring gcc make openssl \
		php7.3-mbstring php7.3-json php7.3-ctype php7.3-curl -y && \
	apt-get install wget composer -y && \
	wget https://github.com/swoole/swoole-src/archive/v4.5.0.tar.gz && \
	tar -zxvf v4.5.0.tar.gz && \
	cd swoole-src-4.5.0/ && \
	phpize7.3 && ./configure --enable-openssl && make -j2 && make install && \
	(echo "extension=swoole.so" >> $(php -i | grep "Loaded Configuration File" | awk '{print $5}'))
ADD start.sh /home/user/start.sh
RUN chown user:user /home/user/start.sh && chmod +x /home/user/start.sh
ADD https://github.com/zhamao-robot/zhamao-framework/archive/master.zip /home/user/master.zip
RUN chown user:user /home/user/master.zip && chmod 777 /home/user/master.zip
VOLUME ["/home/user/coolq","/home/user/zhamao-framework"]
