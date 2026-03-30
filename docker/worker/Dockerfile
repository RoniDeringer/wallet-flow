FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        sockets \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

