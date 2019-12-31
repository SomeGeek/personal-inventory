FROM php:7.4-fpm
RUN apt-get update 1> /dev/null \
    && apt-get install -y git zip unzip libgd3 \
        libpng-dev libwebp-dev libjpeg62-turbo-dev libxpm-dev libfreetype6-dev libzip-dev zlib1g-dev libicu-dev libgd-dev \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get -y autoremove 1> /dev/null \
    && apt-get clean 1> /dev/null \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install mongodb-1.5.1 \
    && docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg --with-xpm --with-freetype \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl bcmath exif gd

COPY ./docker/app/inventory-php.ini /usr/local/etc/php/conf.d/inventory-php.ini

WORKDIR /app

COPY --chown=www-data:www-data . /app

RUN chown -R www-data:www-data /app

USER www-data

RUN sh ./bin/download_frontend_assets.sh
RUN COMPOSER_CACHE_DIR=/app/cache php -d memory_limit=-1 `which composer` install

CMD ["php-fpm", "--nodaemonize"]
EXPOSE 9000
