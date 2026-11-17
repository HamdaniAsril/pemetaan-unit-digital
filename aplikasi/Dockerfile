# Aplikasi Pemetaan Unit Digital — satu container PHP-FPM + Nginx (port 8080 di dalam container).
FROM serversideup/php:8.3-fpm-nginx

USER root
RUN install-php-extensions intl gd
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/

USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev \
 && php artisan package:discover --ansi

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_STORAGE_LINK=false
