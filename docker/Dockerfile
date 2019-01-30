FROM php:7.2
WORKDIR /root
RUN echo "Asia/Shanghai" > /etc/timezone
RUN dpkg-reconfigure -f noninteractive tzdata
ENV LANG C.UTF-8
RUN apt-get update && apt-get install curl libxml2 libzip-dev openssl git -y

# Install php extensions
RUN docker-php-ext-install zip
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install iconv
RUN docker-php-ext-install mbstring

RUN pecl install swoole
RUN docker-php-ext-enable swoole

VOLUME ["/root/"]

CMD if [ ! -d "CQBot-swoole" ]; then git clone https://github.com/crazywhalecc/CQBot-swoole.git; fi && cd CQBot-swoole/ && bash -c "php start.php"