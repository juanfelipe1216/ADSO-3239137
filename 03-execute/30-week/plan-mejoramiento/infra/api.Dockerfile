FROM php:8.4-cli-bookworm@sha256:b3e907a1108629c562c4bb313ce3449e34d49991f6bb2487913f6146f47200fe
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_pgsql mbstring dom xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY api/composer.json api/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY api/ ./
COPY infra/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
RUN mkdir -p bootstrap/cache storage/framework/views storage/logs \
    && composer dump-autoload --no-scripts
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
