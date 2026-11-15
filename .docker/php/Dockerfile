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
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# PHP
RUN set -eux; \
	install-php-extensions \
        gd \
		apcu \
		intl \
		zip \
        xdebug \
        pdo_mysql \
        pcntl \
	;

# GeoIP
RUN set -xe \
    && mkdir -p /var/www \
    && curl -fL -o /var/www/GeoLite2-City.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-City.mmdb \
    && curl -fL -o /var/www/GeoLite2-ASN.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-ASN.mmdb \
    && chmod 644 /var/www/GeoLite2-City.mmdb /var/www/GeoLite2-ASN.mmdb

# 7zip
RUN set -xe \
    && cd /opt \
    && mkdir 7z2606-linux-x64 \
    && cd /opt/7z2606-linux-x64 \
    && curl -fL -o 7z.tar.xz "https://github.com/ip7z/7zip/releases/download/26.06/7z2606-linux-x64.tar.xz" \
    && tar xJf 7z.tar.xz \
    && rm -f 7z.tar.xz

# Cleanup
RUN set -eux; \
	rm -rf /tmp/*

WORKDIR /var/www/wapinet

