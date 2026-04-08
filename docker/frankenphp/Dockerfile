FROM dunglas/frankenphp:1-builder-php8.5-alpine

RUN install-php-extensions \
    pdo_mysql \
    xdebug \
    memcached

RUN apk update && \
    apk add --no-cache netcat-openbsd

# todo: prod
#COPY . .
