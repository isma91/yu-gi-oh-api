# pour recuperer la config de production plus tard dans le Dockerfile
FROM php:8.2-fpm-alpine as php

# multi-stage build
FROM alpine:3.19

# where we gonna work all the rest
WORKDIR /var/www/html

# datetime / timezone
ARG TZ='Europe/Paris'
ENV DEFAULT_TZ ${TZ}
ENV TZ ${TZ}

RUN apk --update --no-cache --no-progress add tzdata && \
    cp /usr/share/zoneinfo/${DEFAULT_TZ} /etc/localtime

# Dependance install with nginx, php 8.2 and his extensions
RUN apk add --no-cache --no-progress \
  curl-dev \
  musl-dev \
	libcurl \
	libzip-dev \
	bzip2-dev \
	git \
	ca-certificates \
	autoconf \
	make \
	gcc \
	linux-headers \
  curl \
  nginx \
  php82 \
  php82-fpm \
  php82-ctype \
  php82-curl \
  php82-dom \
  php82-fileinfo \
  php82-fpm \
  php82-gd \
  php82-intl \
  php82-mbstring \
  php82-mysqli \
  php82-opcache \
  php82-openssl \
  php82-phar \
  php82-session \
  php82-tokenizer \
  php82-xml \
  php82-simplexml \
  php82-xmlreader \
  php82-xmlwriter \
  php82-pdo_mysql \
  php82-iconv 


# config of php
ENV PHP_INI_DIR /etc/php82
# get the official production config of php from the multistage build
COPY --from=php /usr/local/etc/php/php.ini-production /etc/php82/php.ini
# add custom config
COPY config-server/php.ini ${PHP_INI_DIR}/conf.d/custom.ini
# config of nginx
COPY config-server/nginx.conf /etc/nginx/nginx.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 8080

COPY config-server/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]

# healthcheck to add later
# HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping || exit 1