FROM php:8.4-apache

# System libraries and PHP extensions required by WordPress/Bedrock (gd +
# exif for media, intl, opcache, pdo_mysql/mysqli, zip, apcu for caching).
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        exif \
        gd \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        zip \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

# Bundle Composer so `composer install` can run inside the container
# (Bedrock's own dependencies: WordPress core, mu-plugins, wpackagist
# plugins/themes).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Bundle WP-CLI for `wp` commands inside the container (core install,
# plugin/theme management, cache flush, etc.).
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# Point the vhost DocumentRoot at Bedrock's webroot (web/), which holds
# web/wp (WordPress core) and web/app (the wp-content equivalent).
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Raise default PHP limits for WordPress (memory, uploads, execution time).
COPY php.ini /usr/local/etc/php/conf.d/wordpress.ini

WORKDIR /var/www/html
