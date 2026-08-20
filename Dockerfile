# ---------- Stage 1: Composer dependencies ----------
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
# composer.lock tidak selalu ter-commit di repo -> copy composer.json saja,
# lalu install (bila lock ada) ATAU update (generate lock saat build).
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader \
    || composer update --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------- Stage 2: PHP-FPM runtime ----------
FROM php:8.5-fpm AS app

# Ekstensi PHP via installer standar (auto system deps + configure per versi PHP,
# stabil — menggantikan apt+docker-php-ext-install paralel yang rawan OOM di Docker Desktop).
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring xml gd zip bcmath intl opcache pcntl

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/php/local.ini /usr/local/etc/php/conf.d/zz-eams.ini
COPY docker/entrypoint.sh /usr/local/bin/eams-entrypoint

# Normalisasi line ending CRLF (Windows) -> LF agar shebang #!/bin/sh valid, lalu chmod +x.
# Tanpa ini, script yang ter-checkout CRLF memicu 'no such file or directory' di Linux.
RUN sed -i 's/\r$//' /usr/local/bin/eams-entrypoint \
    && chmod +x /usr/local/bin/eams-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["eams-entrypoint"]
CMD ["php-fpm"]
