# PHP 8.2 FPM for CRM — concurrent request handling (replaces the serial php -S dev server).
# Extensions installed via mlocati's install-php-extensions (handles gd/imap cleanly on bookworm).
FROM php:8.2-fpm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        pdo_mysql \
        mysqli \
        gd \
        imap \
        opcache \
        redis \
        bcmath \
        mbstring \
        exif \
        pcntl \
        zip \
        intl

# Composer (for artisan/composer commands inside the container)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Custom PHP + OPcache tuning and the FPM pool worker count.
COPY php.ini   /usr/local/etc/php/conf.d/zz-crm.ini
COPY www.conf  /usr/local/etc/php-fpm.d/zz-crm.conf

WORKDIR /var/www
