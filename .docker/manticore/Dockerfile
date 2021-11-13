FROM manticoresearch/manticore

RUN set -xe \
    && apt-mark hold manticore \
    && apt-get update \
    && apt-get upgrade -y \
    && apt-get clean \
    && rm -rf /tmp/*
