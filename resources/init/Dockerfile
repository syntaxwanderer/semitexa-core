# Minimal PHP + Swoole for Semitexa (project is mounted at runtime)
FROM php:8.4-cli-alpine

RUN apk add --no-cache autoconf g++ make linux-headers openssl-dev \
    && pecl install --nobuild swoole \
    && cd "$(pecl config-get temp_dir)/swoole" \
    && phpize && ./configure --enable-openssl --disable-brotli --disable-zstd \
    && make -j$(nproc) && make install \
    && docker-php-ext-enable swoole

WORKDIR /var/www/html

CMD ["php", "server.php"]
