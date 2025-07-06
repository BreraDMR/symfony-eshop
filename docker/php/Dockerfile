FROM php:8.4-fpm-bookworm

# PHP extensions are installed with a small helper image that resolves the
# system libraries for us.
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        pdo_pgsql \
        intl \
        opcache \
        redis \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/app

# The application code is bind-mounted in development (see compose.yaml),
# so we only need the runtime here.
