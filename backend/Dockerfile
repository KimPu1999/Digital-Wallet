FROM composer:2.8.12 AS vendor
WORKDIR /build
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --optimize-autoloader

FROM php:8.3.25-fpm-bookworm
ARG APP_ENV=production
ENV APP_ENV=${APP_ENV} APP_DEBUG=false
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq5 libzip4 \
    && docker-php-ext-install pdo_pgsql bcmath opcache \
    && pecl install redis-6.1.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY --from=vendor /build/vendor ./vendor
COPY backend/ .
COPY infra/docker/backend/php.ini /usr/local/etc/php/conf.d/zz-platform.ini
COPY infra/docker/backend/entrypoint.sh /usr/local/bin/platform-entrypoint
RUN chmod 0555 /usr/local/bin/platform-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
ENTRYPOINT ["platform-entrypoint"]
CMD ["php-fpm", "-F"]
