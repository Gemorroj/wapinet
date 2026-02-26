FROM php:8.5-fpm

# Update repo and install some utilites
RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    htop \
    mc \
	acl \
	file \
	gettext \
	git \
    ffmpeg \
	&& rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# PHP
RUN set -eux; \
	install-php-extensions \
        gd \
		apcu \
		intl \
		opcache \
		zip \
        xdebug \
        pdo_mysql \
        pcntl \
	;

# GeoIP
RUN set -xe \
    && curl -L -o /GeoLite2-Country.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-Country.mmdb \
    && chmod 644 /GeoLite2-Country.mmdb

# 7zip
RUN set -xe \
    && cd /opt \
    && mkdir 7z2600-linux-x64 \
    && cd /opt/7z2600-linux-x64 \
    && curl -O -L https://7-zip.org/a/7z2600-linux-x64.tar.xz \
    && tar xJvf 7z2600-linux-x64.tar.xz \
    && rm -f 7z2600-linux-x64.tar.xz

# Cleanup
RUN set -eux; \
	rm -rf /tmp/*

WORKDIR /var/www/app

