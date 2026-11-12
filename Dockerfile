FROM php:8.5-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    bcmath \
    soap \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" > /usr/local/etc/php/conf.d/xdebug.ini


# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node and npm, so npm runs here too (the node service runs Vite on this same image). Only node and npm's folder are
# copied: the whole of /usr/local/bin would overwrite PHP's own. The slim image is Debian, like this one.
COPY --from=node:26-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:26-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Run as a user whose uid/gid match the host user, so files created inside the
# container (artisan make:, logs, caches) stay owned by, and editable for, the host.
ARG UID=1000
ARG GID=1000
RUN groupadd --gid ${GID} laravel \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash laravel

WORKDIR /var/www


COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh

USER laravel

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-F"]
