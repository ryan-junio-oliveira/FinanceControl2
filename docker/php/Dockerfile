# ---------- Stage 1: front-end ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY resources/ ./resources/
COPY vite.config.js ./
RUN npm run build

# ---------- Stage 2: PHP ----------
FROM php:8.3-fpm-alpine

# Dependências de sistema para as extensões PHP
RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        pcntl \
        opcache \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del icu-dev libzip-dev oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Dependências PHP (sem dev, sem scripts no primeiro passo)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Código + assets compilados
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/finfamiglia.ini
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
