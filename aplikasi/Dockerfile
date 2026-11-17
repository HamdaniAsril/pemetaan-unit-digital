# Aplikasi Pemetaan Unit Digital: satu container PHP-FPM + Nginx (port 8080 di dalam container).
# Tiga tahap: dasar (PHP + ekstensi), uji (seluruh test dijalankan saat build), produksi (yang dijalankan).
FROM serversideup/php:8.3-fpm-nginx AS dasar
USER root
RUN install-php-extensions intl gd
USER www-data
WORKDIR /var/www/html

# Dipakai deploy/deploy.sh: `docker build --target uji`. Satu test gagal = build gagal = deploy berhenti,
# dan versi yang sedang berjalan tidak tersentuh.
FROM dasar AS uji
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY --chown=www-data:www-data . .
RUN composer dump-autoload \
 && php artisan package:discover --ansi \
 && cp .env.example .env && php artisan key:generate --ansi \
 && php vendor/bin/pest --colors=never

FROM dasar AS produksi
USER root
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/
USER www-data
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY --chown=www-data:www-data . .
RUN rm -rf tests \
 && composer dump-autoload --optimize --no-dev \
 && php artisan package:discover --ansi

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_STORAGE_LINK=false
