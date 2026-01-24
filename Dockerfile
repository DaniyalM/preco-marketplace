# Stage 1: Unified Builder (PHP + Node.js)
FROM php:8.4-cli-alpine AS builder

# Set System Environment for Build
ENV APP_ENV=production

# Install the high-speed extension installer (No --chmod)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install System Dependencies + PHP Extensions
RUN apk add --no-cache nodejs npm git zip unzip
RUN install-php-extensions swoole redis mongodb pcntl gd zip pdo_mysql intl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# 1. Install PHP Dependencies (No scripts to avoid DB connection errors)
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN rm -rf bootstrap/cache/*.php
RUN php artisan package:discover --ansi
RUN php artisan config:clear
RUN php artisan view:clear
RUN php artisan octane:install --server=swoole
# 2. Build Frontend
# NOTE: Ensure your vite.config.ts has the production check for wayfinder
RUN npm install && npm run build


# Stage 2: Final Production Image
FROM php:8.4-cli-alpine

ENV APP_ENV=production
ENV OCTANE_SERVER=swoole

# Install the installer again (No --chmod)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Re-install extensions in final image (using cache if possible)
RUN install-php-extensions swoole redis mongodb pcntl gd zip pdo_mysql intl bcmath

WORKDIR /var/www

# Copy only necessary files from builder
# This brings over your vendor/ folder and public/build/ folder
COPY --from=builder /app /var/www

# Optimize Permissions for Octane/Swoole
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

RUN apk add --no-cache nodejs npm
# Start Octane
ENTRYPOINT ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto"]
