FROM dunglas/frankenphp:1.14-php8.5

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
        util-linux \
        ffmpeg \
        unzip; \
    rm -rf /var/lib/apt/lists/*

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/var/www/.composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

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

# UID/GID как у владельца bind-mount'а.
RUN set -eux; \
    groupmod -o -g "${LOCAL_GID}" www-data; \
    usermod -o -u "${LOCAL_UID}" -g "${LOCAL_GID}" www-data; \
    mkdir -p /var/www/.composer /config/caddy /data/caddy; \
    chown -R www-data:www-data /var/www/.composer /config /data; \
    ln -s /opt/7z2606-linux-x64/7zz /usr/local/bin/7zz

COPY conf.d/ /usr/local/etc/php/conf.d/

USER www-data

EXPOSE 8080
