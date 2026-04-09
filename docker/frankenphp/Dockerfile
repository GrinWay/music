FROM dunglas/frankenphp:1-builder-php8.5-alpine

RUN install-php-extensions \
    pdo_mysql \
    xdebug \
    memcached

RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN apk update && \
    apk add --no-cache netcat-openbsd

RUN printf '#!/bin/sh\nphp /app/bin/console "$@"\n' > /usr/local/bin/music \
 && chmod +x /usr/local/bin/music
