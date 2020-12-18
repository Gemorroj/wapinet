FROM php:7.4-fpm-alpine

RUN set -xe \
    && apk update \
    && apk upgrade \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \

    # ZIP
    && apk add --no-cache libzip libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-enable zip \

    # Sodium
    && apk add --no-cache libsodium-dev \
    && docker-php-ext-install sodium \
    && docker-php-ext-enable sodium \

    # INTL
    && apk add --no-cache icu-dev icu-libs \
    && docker-php-ext-install intl \
    && docker-php-ext-enable intl \

    # Mysql
    && apk add --no-cache mysql-client \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable pdo_mysql \

    # GD
    && apk add --no-cache freetype libpng libwebp libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure gd \
        --enable-gd \
        --with-freetype \
        --with-webp \
        --with-jpeg \
    && NPROC=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) \
    && docker-php-ext-install -j${NPROC} gd \
    && docker-php-ext-enable gd \

    # Xdebug (use latest 2.x)
    && pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug \

    # APCU
    && pecl install apcu \
    && docker-php-ext-enable apcu \

    # Cleanup
    && apk del --no-cache .build-deps \
    && apk del --no-cache icu-dev \
    && apk del --no-cache libsodium-dev \
    && apk del --no-cache libzip-dev \
    && apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && rm -rf /tmp/* /var/cache/apk/*


# Composer
RUN set -xe \
    && curl -L -o /composer.phar https://github.com/composer/composer/releases/download/1.10.19/composer.phar \
    && chmod 755 /composer.phar


# utilites
RUN set -xe \
    && apk --no-cache add htop unzip gzip p7zip git


# GeoIP
RUN set -xe \
    && curl -L -o /GeoLite2-Country.mmdb.gz https://raw.githubusercontent.com/wp-statistics/GeoLite2-Country/master/GeoLite2-Country.mmdb.gz \
    && gzip -d /GeoLite2-Country.mmdb.gz \
    && chmod 644 /GeoLite2-Country.mmdb


WORKDIR /var/www/app
