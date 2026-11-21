FROM php:8.5-fpm

# upgrade: dev-образ со свежими пакетами на момент сборки
RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
        acl \
        file \
        gettext \
        git \
        htop \
        mc \
        ffmpeg \
        unzip; \
    rm -rf /var/lib/apt/lists/*

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# PIE не подходит: ставит только Composer-пакеты, а gd/intl/zip/pcntl/pdo_mysql — bundled-расширения,
# и системные зависимости не тянет. Поэтому mlocati/docker-php-extension-installer.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    install-php-extensions \
        apcu \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        xdebug \
        zip

RUN set -eux; \
    mkdir -p /var/www; \
    curl -fsSL -o /var/www/GeoLite2-City.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-City.mmdb; \
    curl -fsSL -o /var/www/GeoLite2-ASN.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-ASN.mmdb; \
    chmod 644 /var/www/GeoLite2-City.mmdb /var/www/GeoLite2-ASN.mmdb

RUN set -eux; \
    mkdir -p /opt/7z2606-linux-x64; \
    curl -fsSL -o /tmp/7z.tar.xz https://github.com/ip7z/7zip/releases/download/26.06/7z2606-linux-x64.tar.xz; \
    tar -xJf /tmp/7z.tar.xz -C /opt/7z2606-linux-x64; \
    rm -f /tmp/7z.tar.xz

WORKDIR /var/www/wapinet

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

# UID/GID как у владельца bind-mount'а; FPM работает сразу от www-data
RUN set -eux; \
    groupmod -o -g "${LOCAL_GID}" www-data; \
    usermod -o -u "${LOCAL_UID}" -g "${LOCAL_GID}" www-data; \
    mkdir -p /var/www/.composer; \
    chown www-data:www-data /var/www/.composer; \
    ln -s /opt/7z2606-linux-x64/7zz /usr/local/bin/7zz; \
    sed -i '/^user = /d; /^group = /d' /usr/local/etc/php-fpm.d/www.conf

COPY conf.d/ /usr/local/etc/php/conf.d/

USER www-data
