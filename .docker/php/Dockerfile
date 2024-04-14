FROM php:8.3-fpm-alpine

# Update repo and install some utilites
RUN set -xe \
    && apk update \
    && apk upgrade \
    && apk --no-cache add htop unzip gzip p7zip git ffmpeg linux-headers

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# GeoIP
RUN set -xe \
    && curl -L -o /GeoLite2-Country.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-Country.mmdb \
    && chmod 644 /GeoLite2-Country.mmdb

# PHP
RUN set -xe \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
\
    # Opcache
    && docker-php-ext-install opcache \
    && docker-php-ext-enable opcache \
\
    # Xdebug, APCU
    && pecl install apcu \
    && pecl install xdebug \
    && docker-php-ext-enable apcu xdebug \
\
    # ZIP
    && apk add --no-cache libzip libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-enable zip \
\
    # Sodium
    && apk add --no-cache libsodium-dev \
    && docker-php-ext-install sodium \
    && docker-php-ext-enable sodium \
\
    # INTL
    && apk add --no-cache icu-dev icu-libs \
    && docker-php-ext-install intl \
    && docker-php-ext-enable intl \
\
    # Mysql
    && apk add --no-cache mysql-client \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable pdo_mysql \
\
    # GD
    && apk add --no-cache freetype libpng libwebp libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd \
        --enable-gd \
        --with-freetype \
        --with-webp \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
\
    # PCNTL
    && docker-php-ext-install pcntl \
    && docker-php-ext-enable pcntl

# Cleanup
RUN set -xe \
    && apk del --no-cache .build-deps \
    && apk del --no-cache icu-dev \
    && apk del --no-cache libsodium-dev \
    && apk del --no-cache libzip-dev \
    && apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && rm -rf /tmp/* /var/cache/apk/*


WORKDIR /var/www/app
