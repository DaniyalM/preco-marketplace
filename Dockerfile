# =============================================================================
# P-Commerce Dockerfile
# Multi-stage build for Laravel Octane with Swoole
# Optimized for stateless JWT-based authentication
# =============================================================================

# Stage 1: Builder (PHP + Node.js for building assets)
FROM php:8.4-cli-alpine AS builder

ENV APP_ENV=production
ENV NODE_ENV=production

# Install extension installer (compatible with non-BuildKit)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install system dependencies
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    zip \
    unzip \
    curl

# Install PHP extensions
RUN install-php-extensions \
    swoole \
    redis \
    pcntl \
    gd \
    zip \
    pdo_pgsql \
    intl \
    bcmath \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy dependency files first (for better caching)
COPY composer.json composer.lock ./
COPY package.json package-lock.json* ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Install Node dependencies
RUN npm ci --only=production=false

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Clear caches and prepare Laravel
RUN rm -rf bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan octane:install --server=swoole --no-interaction

# Build frontend assets (client + SSR)
RUN npm run build && npm run build -- --ssr

# Remove dev dependencies but keep production node_modules for SSR
RUN npm prune --production \
    && rm -rf .git \
    && rm -rf tests \
    && rm -rf .github \
    && rm -rf docker \
    && rm -f .env* \
    && rm -f phpunit.xml \
    && rm -f vite.config.ts \
    && rm -f tailwind.config.js \
    && rm -f postcss.config.js


# Stage 2: Production Image
FROM php:8.4-cli-alpine AS production

LABEL maintainer="P-Commerce Team"
LABEL description="P-Commerce Laravel Octane Application"

ENV APP_ENV=production
ENV OCTANE_SERVER=swoole
ENV LOG_CHANNEL=stderr

# Install extension installer (compatible with non-BuildKit)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Install runtime PHP extensions
RUN install-php-extensions \
    swoole \
    redis \
    pcntl \
    gd \
    zip \
    pdo_pgsql \
    intl \
    bcmath \
    opcache

# Install runtime dependencies (including Node.js for SSR)
RUN apk add --no-cache \
    curl \
    ca-certificates \
    nodejs \
    npm

# Create non-root user
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www

WORKDIR /var/www

# Copy application from builder
COPY --from=builder --chown=www:www /app /var/www

# Create required directories with proper permissions
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# NOTE: We do NOT switch to www user here - entrypoint handles that after fixing permissions

# Create startup script that ensures permissions and runs as www user
RUN echo '#!/bin/sh' > /entrypoint.sh && \
    echo 'set -e' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    echo '# Ensure storage directories exist and are writable' >> /entrypoint.sh && \
    echo 'mkdir -p /var/www/storage/framework/cache/data' >> /entrypoint.sh && \
    echo 'mkdir -p /var/www/storage/framework/sessions' >> /entrypoint.sh && \
    echo 'mkdir -p /var/www/storage/framework/views' >> /entrypoint.sh && \
    echo 'mkdir -p /var/www/storage/logs' >> /entrypoint.sh && \
    echo 'mkdir -p /var/www/bootstrap/cache' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    echo '# Set permissions (runs as root)' >> /entrypoint.sh && \
    echo 'chown -R www:www /var/www/storage /var/www/bootstrap/cache' >> /entrypoint.sh && \
    echo 'chmod -R 775 /var/www/storage /var/www/bootstrap/cache' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    echo '# Run the command as www user' >> /entrypoint.sh && \
    echo 'exec su-exec www "$@"' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Install su-exec for dropping privileges
RUN apk add --no-cache su-exec

EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD curl -f http://localhost:8000/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--task-workers=auto", "--max-requests=1000"]
