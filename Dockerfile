# ─────────────────────────────────────────────────────────────────────────────
# Stage 1: Node — build frontend assets
# ─────────────────────────────────────────────────────────────────────────────
FROM node:24-alpine AS node_builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline

COPY resources/ resources/
COPY vite.config.ts tsconfig.json tailwind.config.js ./
COPY public/ public/

RUN npm run build


# ─────────────────────────────────────────────────────────────────────────────
# Stage 2: PHP base — shared extensions and config
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.5-fpm-alpine AS php_base

# Build tools + runtime dependencies
# $PHPIZE_DEPS includes: autoconf, gcc, g++, make, pkgconf, re2c, etc.
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    bash \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    icu-libs \
    oniguruma-dev \
    linux-headers

# Extensions that require compilation
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    mbstring \
    pcntl

# opcache is bundled and active by default in PHP 8.5 — no install or enable needed
# tuning is applied via opcache.ini (see production stage / docker/php/opcache.ini)

# Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Remove build tools to keep the image lean
RUN apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www


# ─────────────────────────────────────────────────────────────────────────────
# Stage 3: dev — mounts source volume + Node.js + Xdebug
# ─────────────────────────────────────────────────────────────────────────────
FROM php_base AS dev

# Node.js via apk — надёжнее чем COPY --from=node, т.к. Alpine правильно
# собирает все внутренние пути npm/npx без ручного копирования структуры пакетов
RUN apk add --no-cache nodejs npm

# Build tools are needed again to compile Xdebug
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Xdebug config — mode is controlled via XDEBUG_MODE env variable
# Default: off (zero overhead when not debugging)
# Set XDEBUG_MODE=debug in .env or docker-compose for step debugging
# Set XDEBUG_MODE=coverage for phpunit coverage reports
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Non-root user matching typical host UID
RUN addgroup -g 1000 appgroup && adduser -u 1000 -G appgroup -s /bin/bash -D appuser

USER appuser


# ─────────────────────────────────────────────────────────────────────────────
# Stage 4: production — bakes source + assets into the image
# ─────────────────────────────────────────────────────────────────────────────
FROM php_base AS production

# Copy application code
COPY . /var/www

# Copy compiled frontend assets from node stage
COPY --from=node_builder /app/public/build /var/www/public/build

# Install Composer dependencies (no dev, optimised autoloader)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# OPcache tuning for production
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Storage & cache directories must be writable
RUN chown -R www-data:www-data \
    /var/www/storage \
    /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]


# ─────────────────────────────────────────────────────────────────────────────
# Stage 5: web — Caddy with baked public assets and automatic HTTPS
# ─────────────────────────────────────────────────────────────────────────────
FROM caddy:2-alpine AS web

WORKDIR /var/www

COPY --from=production /var/www/public /var/www/public
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
