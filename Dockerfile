# ---------- Stage 1: Composer dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

# ---------- Stage 2: PHP-FPM runtime ----------
FROM php:8.5-fpm AS app

# System deps + PHP extensions (pdo_mysql, gd utk dompdf/qr, intl, zip, opcache, pcntl utk queue)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring xml gd zip bcmath intl opcache pcntl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/php/local.ini /usr/local/etc/php/conf.d/zz-eams.ini
COPY docker/entrypoint.sh /usr/local/bin/eams-entrypoint

RUN chmod +x /usr/local/bin/eams-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["eams-entrypoint"]
CMD ["php-fpm"]
