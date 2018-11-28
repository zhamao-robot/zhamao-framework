FROM php:7.2
WORKDIR /root
RUN apt-get update && apt-get upgrade -y && apt-get install curl libxml2 libzip-dev git -y

# Install php extensions
RUN docker-php-ext-install zip mysqli iconv mbstring json

# Install swoole
#ADD https://github.com/swoole/swoole-src/archive/v4.2.9.tar.gz /root/v4.2.9.tar.gz
#RUN cd /root/ && tar -zxvf v4.2.9.tar.gz && cd swoole-src-4.2.9/ && phpize && \
#    ./configure && make && make install
RUN pecl install swoole
RUN docker-php-ext-enable swoole

VOLUME ["/root/"]

CMD if [ ! -d "CQBot-swoole" ]; then git clone https://github.com/crazywhalecc/CQBot-swoole.git; fi && cd CQBot-swoole/ && bash