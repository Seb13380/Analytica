FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    $PHPIZE_DEPS \
    unzip \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_pgsql \
        zip \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/analytica.ini

# Create storage dirs (for fresh volumes)
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
