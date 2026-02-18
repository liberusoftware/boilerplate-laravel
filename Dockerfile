# Supported PHP versions: 8.2, 8.3
# Note: PHP 8.5 is not yet fully supported by all extensions (rdkafka, memcached, swoole)
ARG PHP_VERSION=8.3

###########################################
# Composer dependencies stage
###########################################
FROM php:${PHP_VERSION}-cli-alpine AS composer-deps

WORKDIR /app

# Install required extensions for composer install
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl sockets zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install composer dependencies (no autoloader yet, will optimize in final stage)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --prefer-dist

###########################################
# Main application stage
###########################################
FROM php:${PHP_VERSION}-cli-alpine

LABEL maintainer="SMortexa <seyed.me720@gmail.com>"
LABEL org.opencontainers.image.title="Laravel Octane Dockerfile"
LABEL org.opencontainers.image.description="Production-ready Dockerfile for Laravel Octane"
LABEL org.opencontainers.image.source=https://github.com/exaco/laravel-octane-dockerfile
LABEL org.opencontainers.image.licenses=MIT

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=swoole \
    USER=octane \
    ROOT=/var/www/html \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo ${TZ} > /etc/timezone

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install system dependencies and PHP extensions in one layer
RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
    curl \
    wget \
    nano \
    ncdu \
    procps \
    ca-certificates \
    supervisor \
    libsodium-dev && \
    install-php-extensions \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pgsql \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    rdkafka \
    memcached \
    igbinary \
    ldap \
    swoole && \
    docker-php-source delete && \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.29/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER}

RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R ${USER}:${USER} ${ROOT} /var/log /var/run \
    && chmod -R a+rw ${ROOT} /var/log /var/run

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

USER ${USER}

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy vendor from composer-deps stage for better caching
COPY --chown=${USER}:${USER} --from=composer-deps /app/vendor ./vendor

# Copy composer files (needed for autoloader generation)
COPY --chown=${USER}:${USER} composer.json composer.lock ./

# Generate optimized autoloader with vendor already in place
RUN composer dump-autoload --classmap-authoritative --no-dev && \
    composer clear-cache

# Copy application code
COPY --chown=${USER}:${USER} . .

# Create necessary Laravel directories
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache && \
    chmod -R a+rw storage

# Copy configuration files
COPY --chown=${USER}:${USER} .docker/supervisord.conf /etc/supervisor/
COPY --chown=${USER}:${USER} .docker/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --chown=${USER}:${USER} .docker/supervisord.*.conf /etc/supervisor/conf.d/
COPY --chown=${USER}:${USER} .docker/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --chown=${USER}:${USER} .docker/start-container /usr/local/bin/start-container

# Copy environment file
COPY --chown=${USER}:${USER} .env.example ./.env

RUN chmod +x /usr/local/bin/start-container && \
    cat .docker/utilities.sh >> ~/.bashrc

EXPOSE 8000

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=2s --timeout=5s --retries=8 CMD php artisan octane:status || exit 1

