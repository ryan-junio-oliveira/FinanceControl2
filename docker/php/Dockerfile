# ---------- Stage 1: front-end ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY resources/ ./resources/
COPY vite.config.js ./
RUN npm run build

# ---------- Stage 2: PHP ----------
# 8.5 acompanhando o ambiente local e o composer.lock.
FROM php:8.5-fpm-alpine

# Dependências de sistema para as extensões PHP.
# Runtime (mantidas): *_libs + mariadb-client. Build (removidas no final):
# toolchain (.build-deps) + headers *-dev. Sem isso, o `apk del` leva as
# libs de runtime junto (ext-intl/zip quebram) e o pecl não compila sem gcc.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && apk add --no-cache \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
        mariadb-client \
    && apk add --no-cache --virtual .ext-deps \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # Sem -j: o make paralelo tem race no build de algumas extensões.
    # Sem opcache: no PHP 8.5 ele já vem embutido/ativo na imagem base
    # (--enable-opcache nem existe mais no configure).
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        pcntl \
        sockets \
        gd \
    # phpredis >= 6.3: primeira versão estável com suporte a PHP 8.5.
    # Retry: o download do PECL às vezes vem truncado (falha intermitente).
    && (pecl install redis-6.3.0 || (rm -rf /tmp/pear && sleep 5 && pecl install redis-6.3.0)) \
    && docker-php-ext-enable redis \
    && apk del .build-deps .ext-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Dependências PHP (sem dev, sem scripts no primeiro passo)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Código + assets compilados
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/prumo.ini
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
