FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libwebp-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        intl \
        zip \
        bcmath \
        gd \
        exif \
        pcntl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-atlexpress.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/zz-atlexpress-opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/atlexpress-entrypoint

RUN mkdir -p /var/www/html \
    && chmod +x /usr/local/bin/atlexpress-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["atlexpress-entrypoint"]
CMD ["php-fpm"]