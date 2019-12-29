FROM php:7.4-apache

# Install build dependencies, e.g. composer, system libs
RUN apt-get update 1> /dev/null \
    && apt-get install -y git zip unzip libgd3 \
        libpng-dev libwebp-dev libjpeg62-turbo-dev libxpm-dev libfreetype6-dev libzip-dev zlib1g-dev libicu-dev libgd-dev \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get -y autoremove 1> /dev/null \
    && apt-get clean 1> /dev/null \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# PHP
RUN pecl install mongodb-1.5.1 \
    && docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg --with-xpm --with-freetype \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl bcmath exif gd

COPY ./docker/web/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/web/inventory-php.ini /usr/local/etc/php/conf.d/inventory-php.ini

COPY . /var/www/app

RUN mkdir -p /var/www/app/data \
    && mkdir -p /var/www/app/var/log \
    && mkdir -p /var/www/app/var/cache \
    && chmod 777 -R /var/www/app/data /var/www/app/var/log /var/www/app/var/cache

WORKDIR /var/www/app
RUN php -d memory_limit=-1 `which composer` install
