# DevFlow Pro - Production-Ready Multi-Stage Dockerfile
# Laravel 12 | PHP 8.4-FPM | Alpine Linux
# Optimized for Production with Development Support

# ================================
# Base Stage - Common Dependencies
# ================================
FROM php:8.4-fpm-alpine AS base

# Build arguments
ARG PHP_VERSION=8.4
ARG INSTALL_XDEBUG=false
ARG USER_ID=1000
ARG GROUP_ID=1000

# Metadata labels
LABEL maintainer="DevFlow Pro <admin@devflow.pro>"
LABEL description="DevFlow Pro - Multi-Project Deployment & Management System"
LABEL version="1.0.0"
LABEL php.version="${PHP_VERSION}"

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    # Build dependencies
    $PHPIZE_DEPS \
    # System utilities
    bash \
    curl \
    wget \
    git \
    vim \
    zip \
    unzip \
    shadow \
    # Image processing
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libavif-dev \
    # Archive and compression
    libzip-dev \
    bzip2-dev \
    # Internationalization
    icu-dev \
    icu-data-full \
    # Database drivers
    postgresql-dev \
    postgresql-client \
    # Caching
    redis \
    # Process management
    supervisor \
    # Docker CLI for container management
    docker-cli \
    docker-cli-compose \
    # SSL/TLS
    openssl \
    ca-certificates \
    # Network utilities
    net-tools \
    iputils \
    # File system utilities
    rsync \
    # Memory profiling
    libmemcached-dev \
    zlib-dev \
    # Math libraries
    gmp-dev \
    # XML processing
    libxml2-dev \
    libxslt-dev \
    && rm -rf /var/cache/apk/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
        # Database
        pdo \
        pdo_pgsql \
        pgsql \
        # Image processing
        gd \
        exif \
        # Data handling
        zip \
        # Internationalization
        intl \
        # Math
        bcmath \
        gmp \
        # Process control
        pcntl \
        # Sockets
        sockets \
        # Performance
        opcache \
        # XML
        xml \
        xmlwriter \
        simplexml \
        dom \
        xsl \
        # String manipulation
        mbstring \
        # File info
        fileinfo

# Install PECL extensions
RUN pecl channel-update pecl.php.net \
    && pecl install redis-6.0.2 \
    && pecl install igbinary-3.2.15 \
    && docker-php-ext-enable redis igbinary

# Install Xdebug (development only)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
        pecl install xdebug-3.3.2 \
        && docker-php-ext-enable xdebug; \
    fi

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Create application user and group
RUN if ! getent group www-data > /dev/null; then \
        addgroup -g ${GROUP_ID} -S www-data; \
    fi \
    && if ! id -u www-data > /dev/null 2>&1; then \
        adduser -u ${USER_ID} -D -S -G www-data www-data; \
    fi

# Create necessary directories with proper permissions
RUN mkdir -p \
        /var/www/html/storage/framework/{sessions,views,cache} \
        /var/www/html/storage/logs \
        /var/www/html/storage/app/public \
        /var/www/html/bootstrap/cache \
        /tmp/opcache \
        /var/log/php \
    && chown -R www-data:www-data \
        /var/www/html \
        /tmp/opcache \
        /var/log/php \
    && chmod -R 755 /var/www/html

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Health check script for PHP-FPM
COPY docker/php/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# ================================
# Development Stage
# ================================
FROM base AS development

# Install development dependencies
RUN apk add --no-cache \
    nodejs \
    npm \
    && npm install -g npm@latest

# Copy PHP configuration for development
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache-dev.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Xdebug configuration for development
RUN if [ -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]; then \
        echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi

# Set environment for development
ENV APP_ENV=local \
    APP_DEBUG=true \
    COMPOSER_ALLOW_SUPERUSER=1

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

# ================================
# Builder Stage - Dependencies Installation
# ================================
FROM base AS builder

# Set environment for build
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Copy composer files
COPY --chown=www-data:www-data composer.json composer.lock ./

# Install PHP dependencies (production, optimized)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    && composer clear-cache

# Copy application code
COPY --chown=www-data:www-data . .

# Generate optimized autoloader
RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-dev

# Install Node.js and build frontend assets
RUN apk add --no-cache nodejs npm \
    && npm install \
    && npm run build \
    && rm -rf node_modules \
    && apk del nodejs npm

# ================================
# Production Stage - Final Image
# ================================
FROM base AS production

# Copy PHP configuration for production
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application from builder
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Copy entrypoint scripts
COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY --chown=www-data:www-data docker/scheduler-entrypoint.sh /usr/local/bin/scheduler-entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/scheduler-entrypoint.sh

# Set proper permissions for Laravel directories
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

# Set environment for production
ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Switch to non-root user
USER www-data

# Expose PHP-FPM port
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start PHP-FPM
CMD ["php-fpm"]

# ================================
# Testing Stage
# ================================
FROM development AS testing

USER root

# Install testing tools
RUN composer require --dev \
    phpunit/phpunit \
    mockery/mockery \
    fakerphp/faker \
    --no-interaction --no-update \
    && composer update --no-interaction

# Copy PHPUnit configuration
COPY --chown=www-data:www-data phpunit.xml .

# Set environment for testing
ENV APP_ENV=testing \
    DB_CONNECTION=pgsql \
    DB_HOST=postgres \
    DB_DATABASE=devflow_test \
    CACHE_DRIVER=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync

USER www-data

CMD ["vendor/bin/phpunit"]
