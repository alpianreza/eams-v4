# ---------- Stage 1: Frontend assets (Vite) ----------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: Composer dependencies ----------
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------- Stage 3: PHP-FPM runtime ----------
FROM php:8.2-fpm AS app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring xml gd zip bcmath intl opcache pcntl

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/php/local.ini /usr/local/etc/php/conf.d/zz-eams.ini
COPY docker/entrypoint.sh /usr/local/bin/eams-entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/eams-entrypoint \
    && chmod +x /usr/local/bin/eams-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["eams-entrypoint"]
CMD ["php-fpm"]
