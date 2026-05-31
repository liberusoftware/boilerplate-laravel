ARG PHP_VERSION=8.5
ARG COMPOSER_VERSION=2

FROM composer:${COMPOSER_VERSION} AS vendor

FROM php:${PHP_VERSION}-cli-alpine

LABEL maintainer="Liberu Software <team@liberu.co.uk>"
LABEL org.opencontainers.image.title="Liberu Laravel Boilerplate"
LABEL org.opencontainers.image.description="Production-ready Dockerfile for Laravel Octane (RoadRunner / Swoole / FrankenPHP)"
LABEL org.opencontainers.image.source=https://github.com/liberusoftware/boilerplate-laravel
LABEL org.opencontainers.image.licenses=MIT

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    OCTANE_SERVER=roadrunner \
    TZ=${TZ} \
    LANG=C.UTF-8 \
    USER=laravel \
    ROOT=/var/www/html \
    APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=48 \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    WITH_REVERB=false

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    wget \
    vim \
    tzdata \
    ncdu \
    procps \
    unzip \
    ca-certificates \
    bash \
    supervisor \
    libsodium-dev \
    && install-php-extensions \
    apcu \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    igbinary \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.38/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${GROUP_ID} ${USER} \
    && adduser -D -G ${USER} -u ${USER_ID} -s /bin/sh ${USER}

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link .docker/supervisord.conf /etc/
COPY --link .docker/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link .docker/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link .docker/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.services.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.horizon.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.scheduler.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.reverb.conf /etc/supervisor/conf.d/
COPY --link .docker/supervisord.worker.conf /etc/supervisor/conf.d/
COPY --link .docker/php.ini ${PHP_INI_DIR}/conf.d/99-php.ini
COPY --link .docker/octane/RoadRunner/.rr.prod.yaml ./.rr.yaml
COPY --link .docker/start-container /usr/local/bin/start-container
COPY --link .docker/healthcheck /usr/local/bin/healthcheck
COPY --link composer.* ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts \
    --no-progress \
    --audit

COPY --link . .

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

RUN composer dump-autoload \
    --optimize \
    --apcu \
    --no-dev

RUN if composer show | grep spiral/roadrunner-cli >/dev/null 2>&1; then \
    ./vendor/bin/rr get-binary --quiet && chmod +x rr; \
    else echo "spiral/roadrunner-cli not found, skipping rr binary"; \
    fi

RUN chown -R ${USER_ID}:${GROUP_ID} ${ROOT} \
    && find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true

USER ${USER}

EXPOSE 8000
EXPOSE 6001

ENTRYPOINT ["start-container"]

HEALTHCHECK --start-period=5s --interval=1s --timeout=3s --retries=10 CMD healthcheck || exit 1
