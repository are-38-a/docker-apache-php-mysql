FROM php:8.1-apache
RUN apt-get update && apt-get install -y \
    libonig-dev \
  && docker-php-ext-install pdo_mysql mysqli
COPY ./config/php.ini /usr/local/etc/php/ 
#COPY等で親ディレクトリは参照できない
#と思ったらyamlの方のcontextで参照できるようになるらしい

ENV LANG ja_JP.UTF-8
ENV LANGUAGE ja_JP:ja
ENV LC_ALL ja_JP.UTF-8

ARG UID=1000
RUN useradd -m -u ${UID} docker

USER ${UID}