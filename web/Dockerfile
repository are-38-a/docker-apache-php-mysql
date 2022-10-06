FROM php:8.1-apache
RUN apt-get update && apt-get install -y \
    libonig-dev \
  && docker-php-ext-install pdo_mysql mysqli
COPY ./config/php.ini /usr/local/etc/php/ 
#COPY等で親ディレクトリは参照できない

ARG UID=1000
RUN useradd -m -u ${UID} docker

USER ${UID}