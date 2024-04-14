FROM mysql:8-debian

RUN set -xe \
    && rm /etc/apt/sources.list.d/mysql.list \
    && apt-get update \
    && apt-get upgrade -y \
    && apt-get clean \
    && rm -rf /tmp/*
