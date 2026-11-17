FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_pgsql mbstring dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY api/composer.json api/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY api/ ./
COPY infra/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
RUN mkdir -p bootstrap/cache storage/framework/views storage/logs \
    && composer dump-autoload --no-scripts
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
