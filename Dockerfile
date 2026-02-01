# Stage 1: Builder (PHP + Node.js for building assets)
FROM php:8.4-cli-alpine AS builder

ENV APP_ENV=production
ENV NODE_ENV=production
ENV VITE_APP_URL="${APP_URL}"
ENV VITE_KEYCLOAK_BASE_URL="${KEYCLOAK_BASE_URL}"
ENV VITE_KEYCLOAK_REALM="${KEYCLOAK_REALM}"
ENV VITE_KEYCLOAK_CLIENT_ID="${KEYCLOAK_CLIENT_ID}"

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
    curl \
    bash

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

# Install Node dependencies (need dev dependencies for build)
RUN npm ci --only=production=false

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Clear caches and prepare Laravel
RUN php artisan package:discover --ansi \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

ENV VITE_APP_URL=http://localhost:8000
ENV VITE_KEYCLOAK_BASE_URL=http://localhost:8080

# Build frontend assets (client + SSR)
RUN npm run build

# Generate Vite manifest if not exists and verify
RUN if [ ! -f public/build/manifest.json ]; then \
    echo "Vite manifest not found, running build..." && \
    npm run build; \
    fi

# Verify build files exist
RUN echo "Checking built files:" \
    && ls -la public/build/ || true

# Keep only production node_modules for SSR
RUN npm prune --production

# Remove unnecessary files (but keep Vite config for manifest resolution)
RUN rm -rf .git \
    && rm -rf tests \
    && rm -rf .github \
    && rm -rf docker \
    && rm -f .env* \
    && rm -f phpunit.xml \
    && find . -name "*.stub" -delete \
    && find . -name "*.example" -delete

# Stage 2: Production Image
FROM php:8.4-cli-alpine AS production

LABEL maintainer="P-Commerce Team"
LABEL description="P-Commerce Laravel Octane Application"

ENV APP_ENV=production
ENV OCTANE_SERVER=swoole
ENV LOG_CHANNEL=stderr
ENV VITE_MANIFEST_DIR="/var/www/public/build"

# Install extension installer
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

# Install runtime dependencies
RUN apk add --no-cache \
    curl \
    ca-certificates \
    nodejs \
    npm \
    bash

# Create non-root user
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www

WORKDIR /var/www

# Copy application from builder - IMPORTANT: include the public/build directory
COPY --from=builder --chown=www:www /app /var/www

# Verify Vite manifest exists
RUN echo "Checking Vite manifest in production image:" \
    && ls -la /var/www/public/build/ 2>/dev/null || echo "No build directory found" \
    && if [ -f /var/www/public/build/manifest.json ]; then \
    echo "Vite manifest found, checking contents:" && \
    head -20 /var/www/public/build/manifest.json; \
    else \
    echo "ERROR: Vite manifest not found in production image!"; \
    exit 1; \
    fi

# Create required directories with proper permissions
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create startup script
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'set -e' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    echo '# Generate APP_KEY if not provided' >> /entrypoint.sh && \
    echo 'if [ -z "$APP_KEY" ]; then' >> /entrypoint.sh && \
    echo '  echo "No APP_KEY found, generating one..."' >> /entrypoint.sh && \
    echo '  php /var/www/artisan key:generate --show' >> /entrypoint.sh && \
    echo 'fi' >> /entrypoint.sh && \
    echo '' >> /entrypoint.sh && \
    echo '# Verify Vite manifest exists' >> /entrypoint.sh && \
    echo 'if [ ! -f /var/www/public/build/manifest.json ]; then' >> /entrypoint.sh && \
    echo '  echo "ERROR: Vite manifest not found! Build process failed."' >> /entrypoint.sh && \
    echo '  exit 1' >> /entrypoint.sh && \
    echo 'fi' >> /entrypoint.sh && \
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

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD curl -f http://localhost:8000/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--task-workers=auto", "--max-requests=1000"]
