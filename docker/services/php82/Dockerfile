FROM php:8.2-apache-bullseye

# Install utilities and libraries
RUN apt-get update && apt-get install -y \
    apt-utils wget build-essential cron git curl zip openssl dialog locales \
    libonig-dev libcurl4 libcurl4-openssl-dev libsqlite3-dev libsqlite3-0 zlib1g-dev libzip-dev libpq-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Install APCu PHP extension
RUN pecl install apcu
RUN docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini

# Install Freetype and GD extensions
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql && \
    docker-php-ext-install pgsql && \
    docker-php-ext-install curl && \
    docker-php-ext-install zip && \
    docker-php-ext-install intl && \
    docker-php-ext-install xml && \
    docker-php-ext-install mbstring && \
    docker-php-ext-install exif && \
    docker-php-ext-install gettext

# Install ssh2
RUN apt-get update
RUN apt-get install -y git libssh2-1 libssh2-1-dev
RUN pecl install ssh2-1.3.1
RUN docker-php-ext-enable ssh2

# Enable apache modules
RUN a2enmod rewrite headers expires

# Install french language locale
RUN locale-gen --no-archive fr_CA.UTF-8 \
    locale -a

ENV LANG fr_CA.UTF-8
ENV LANGUAGE fr_CA.UTF-8
ENV LC_ALL fr_CA.UTF-8

# Install cleanup
RUN rm -rf /usr/src/*

# Timezone
ENV TZ "America/Montreal"