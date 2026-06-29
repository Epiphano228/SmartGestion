FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-cli-alpine
RUN apk add --no-cache icu-dev libzip-dev postgresql-dev oniguruma-dev libjpeg-turbo-dev libpng-dev libwebp-dev su-exec \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd intl mbstring pdo_pgsql zip opcache

ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_CLI_SERVER_WORKERS=4

WORKDIR /var/www/html
COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/99-smartgestion.ini

RUN chmod +x docker/start.sh \
    && php artisan package:discover --ansi \
    && php artisan view:cache
RUN php artisan storage:link 2>/dev/null || true
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
CMD ["sh", "docker/start.sh"]