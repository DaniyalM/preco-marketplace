# Local Development Setup (WSL/Linux)

This guide sets up PHP with Swoole locally for fast development iteration. Docker is only used for services (PostgreSQL, Redis, Keycloak).

## Prerequisites

- WSL2 or Native Linux
- Docker (for services only)

---

# Arch Linux Setup

## Step 1: Install PHP 8.4

```bash
# Update system
sudo pacman -Syu

# Install PHP and required extensions
sudo pacman -S --noconfirm \
    php \
    php-gd \
    php-intl \
    php-redis \
    php-pgsql \
    php-sodium

# Verify installation
php -v
```

### Enable PHP Extensions

Edit `/etc/php/php.ini` and uncomment these lines:

```bash
sudo nano /etc/php/php.ini
```

Uncomment (remove the `;` at the start):
```ini
extension=bcmath
extension=curl
extension=gd
extension=intl
extension=mbstring
extension=pdo_pgsql
extension=pgsql
extension=redis
extension=sodium
extension=zip
extension=pcntl
```

---

## Step 2: Install Swoole Extension

```bash
# Install build dependencies
sudo pacman -S --noconfirm base-devel curl openssl c-ares

# Install yay (AUR helper) if not installed
sudo pacman -S --noconfirm git
git clone https://aur.archlinux.org/yay.git /tmp/yay
cd /tmp/yay && makepkg -si --noconfirm
cd -

# Install Swoole from AUR
yay -S --noconfirm php-swoole

# OR compile from source (if AUR package has issues):
git clone https://github.com/swoole/swoole-src.git /tmp/swoole
cd /tmp/swoole
git checkout v5.1.1
phpize
./configure --enable-openssl --enable-http2 --enable-sockets --enable-swoole-curl --enable-cares
make -j$(nproc)
sudo make install
cd -

# Enable Swoole
echo "extension=swoole.so" | sudo tee /etc/php/conf.d/swoole.ini

# Verify Swoole is installed
php -m | grep swoole
php --ri swoole
```

---

## Step 3: Install Composer

```bash
# Install Composer via pacman
sudo pacman -S --noconfirm composer

# OR download directly
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify
composer --version
```

---

## Step 4: Install Node.js (for Vite)

```bash
# Install Node.js LTS
sudo pacman -S --noconfirm nodejs npm

# Verify
node -v
npm -v
```

---

# Ubuntu/Debian Setup (Alternative)

<details>
<summary>Click to expand Ubuntu/Debian instructions</summary>

## Step 1: Install PHP 8.4

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 with required extensions
sudo apt install -y \
    php8.4 \
    php8.4-cli \
    php8.4-common \
    php8.4-curl \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-zip \
    php8.4-pgsql \
    php8.4-redis \
    php8.4-gd \
    php8.4-intl \
    php8.4-bcmath \
    php8.4-pcntl \
    php8.4-dev \
    php-pear

# Verify installation
php -v
```

## Step 2: Install Swoole Extension

```bash
# Install build dependencies
sudo apt install -y \
    build-essential \
    libcurl4-openssl-dev \
    libssl-dev \
    libc-ares-dev

# Install Swoole via PECL
sudo pecl install swoole

# When prompted:
# - enable sockets supports? [no] : yes
# - enable openssl support? [no] : yes
# - enable http2 support? [no] : yes
# - enable mysqlnd support? [no] : no
# - enable curl support? [no] : yes
# - enable cares support? [no] : yes

# Enable Swoole in PHP
echo "extension=swoole.so" | sudo tee /etc/php/8.4/cli/conf.d/20-swoole.ini

# Verify Swoole is installed
php -m | grep swoole
php --ri swoole
```

## Step 3: Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## Step 4: Install Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

</details>

---

## Step 5: Start Docker Services Only

```bash
cd /home/workspace/p-commerce/p-com

# Start only the services (not the app)
docker-compose up -d postgres redis keycloak

# Wait for services to be ready
docker-compose logs -f keycloak  # Wait until "Running the server" appears
# Press Ctrl+C to exit logs
```

---

## Step 6: Configure Environment

```bash
# Copy environment file if not exists
cp .env.example .env

# Update .env for local development:
```

Edit `.env` with these values:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database - connect to Docker PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pcommerce
DB_USERNAME=pcommerce
DB_PASSWORD=postgres

# Redis - connect to Docker Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Keycloak - Docker Keycloak
KEYCLOAK_BASE_URL=http://localhost:8080
KEYCLOAK_INTERNAL_URL=http://localhost:8080
KEYCLOAK_REALM=pcommerce
KEYCLOAK_CLIENT_ID=pcommerce-app
KEYCLOAK_CLIENT_SECRET=

# Session (stateless)
SESSION_DRIVER=array
```

---

## Step 7: Install Dependencies

```bash
cd /home/workspace/p-commerce/p-com

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Generate app key (if not set)
php artisan key:generate

# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed
```

---

## Step 8: Start Development Servers

Open **two terminal windows**:

**Terminal 1 - Laravel Octane (Backend):**
```bash
cd /home/workspace/p-commerce/p-com
php artisan octane:start --watch --host=127.0.0.1 --port=8000
```

**Terminal 2 - Vite (Frontend):**
```bash
cd /home/workspace/p-commerce/p-com
npm run dev
```

---

## Step 9: Access the Application

| Service | URL |
|---------|-----|
| App | http://localhost:8000 |
| Vite HMR | http://localhost:5173 |
| Keycloak Admin | http://localhost:8080/admin |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |

---

## Daily Development Workflow

```bash
# 1. Start Docker services (if not running)
docker-compose up -d postgres redis keycloak

# 2. Start Octane (auto-reloads on PHP changes)
php artisan octane:start --watch

# 3. Start Vite (in another terminal, for frontend HMR)
npm run dev
```

---

## Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Restart Octane workers (after config changes)
php artisan octane:reload

# Run tests
php artisan test

# Check logs
tail -f storage/logs/laravel.log

# Database operations
php artisan migrate:fresh --seed  # Reset and reseed
php artisan tinker               # Interactive shell
```

---

## Troubleshooting

### Swoole Installation Fails

```bash
# If PECL fails, try compiling from source:
git clone https://github.com/swoole/swoole-src.git
cd swoole-src
git checkout v5.1.1  # Use latest stable
phpize
./configure --enable-openssl --enable-http2 --enable-sockets --enable-swoole-curl
make -j$(nproc)
sudo make install
echo "extension=swoole.so" | sudo tee /etc/php/8.4/cli/conf.d/20-swoole.ini
```

### Port Already in Use

```bash
# Find and kill process on port 8000
sudo lsof -i :8000
kill -9 <PID>

# Or use different port
php artisan octane:start --port=8080
```

### Database Connection Failed

```bash
# Check if PostgreSQL container is running
docker-compose ps postgres

# Check if port is accessible
nc -zv 127.0.0.1 5432
```

### Keycloak Not Accessible

```bash
# Check Keycloak logs
docker-compose logs keycloak

# Restart if stuck
docker-compose restart keycloak
```

---

## Cleanup (When Project Goes Live)

Once the project is deployed to production, you can remove all local development dependencies to free up space and clean your WSL environment.

### Step 1: Stop All Running Services

```bash
# Stop Octane if running
# Press Ctrl+C in the Octane terminal

# Stop Vite if running  
# Press Ctrl+C in the Vite terminal

# Stop Docker services
cd /home/workspace/p-commerce/p-com
docker-compose down
```

### Step 2: Remove PHP and All Extensions

**Arch Linux:**
```bash
# Remove PHP and extensions
sudo pacman -Rns php php-gd php-intl php-redis php-pgsql php-sodium

# Remove Swoole (if installed from AUR)
yay -Rns php-swoole

# Remove Swoole compiled files (if installed from source)
sudo rm -f /usr/lib/php/modules/swoole.so
sudo rm -f /etc/php/conf.d/swoole.ini
```

**Ubuntu/Debian:**
```bash
# Remove PHP 8.4 and all related packages
sudo apt remove --purge php8.4* -y

# Remove PECL and PHP development tools
sudo apt remove --purge php-pear php-dev -y

# Remove Swoole compiled files (if installed from source)
sudo rm -f /usr/local/lib/php/extensions/*/swoole.so
sudo rm -f /etc/php/*/cli/conf.d/*swoole.ini

# Remove ondrej PHP repository
sudo add-apt-repository --remove ppa:ondrej/php -y

# Clean up orphaned packages
sudo apt autoremove -y
sudo apt autoclean
```

### Step 3: Remove Composer

```bash
# Remove Composer binary (both methods)
sudo pacman -Rns composer 2>/dev/null  # Arch
sudo rm -f /usr/local/bin/composer

# Remove Composer cache and config
rm -rf ~/.composer
rm -rf ~/.config/composer
rm -rf ~/.cache/composer
```

### Step 4: Remove Node.js and npm

**Arch Linux:**
```bash
# Remove Node.js
sudo pacman -Rns nodejs npm

# Remove npm cache
rm -rf ~/.npm
rm -rf ~/.node-gyp
```

**Ubuntu/Debian:**
```bash
# Remove Node.js
sudo apt remove --purge nodejs -y

# Remove npm global packages
sudo rm -rf /usr/local/lib/node_modules
sudo rm -rf /usr/local/bin/npm
sudo rm -rf /usr/local/bin/npx

# Remove Node.js repository
sudo rm -f /etc/apt/sources.list.d/nodesource.list

# Remove npm cache
rm -rf ~/.npm
rm -rf ~/.node-gyp
```

### Step 5: Remove Build Dependencies

**Arch Linux:**
```bash
# Remove build tools
sudo pacman -Rns base-devel c-ares

# Clean package cache
sudo pacman -Sc
```

**Ubuntu/Debian:**
```bash
# Remove build tools installed for Swoole
sudo apt remove --purge build-essential libcurl4-openssl-dev \
    libssl-dev libc-ares-dev linux-headers-generic -y

sudo apt autoremove -y
```

### Step 6: Remove Docker Resources (Optional)

Only do this if you don't need Docker for other projects:

```bash
# Stop and remove all project containers and volumes
docker-compose down -v --rmi local

# Remove unused Docker images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove unused networks
docker network prune

# Nuclear option - remove EVERYTHING from Docker
# WARNING: This affects ALL Docker projects!
docker system prune -a --volumes
```

### Step 7: Remove Project Files (Optional)

```bash
# Remove node_modules (large folder)
rm -rf /home/workspace/p-commerce/p-com/node_modules

# Remove vendor folder
rm -rf /home/workspace/p-commerce/p-com/vendor

# Remove the entire project
rm -rf /home/workspace/p-commerce/p-com
```

### Step 8: Verify Cleanup

```bash
# Check PHP is removed
php -v  # Should show "command not found"

# Check Composer is removed
composer -V  # Should show "command not found"

# Check Node is removed
node -v  # Should show "command not found"

# Check Docker containers
docker ps -a  # Should not show pcommerce containers

# Check disk space freed
df -h
```

### One-Command Full Cleanup Script

Save this as `cleanup-dev.sh` and run when ready:

```bash
#!/bin/bash
# Full cleanup script - removes all development dependencies

echo "Stopping services..."
docker-compose down -v 2>/dev/null

echo "Removing PHP..."
sudo apt remove --purge php8.4* php-pear -y
sudo add-apt-repository --remove ppa:ondrej/php -y

echo "Removing Composer..."
sudo rm -f /usr/local/bin/composer
rm -rf ~/.composer ~/.config/composer ~/.cache/composer

echo "Removing Node.js..."
sudo apt remove --purge nodejs -y
sudo rm -f /etc/apt/sources.list.d/nodesource.list
rm -rf ~/.npm ~/.node-gyp

echo "Removing build dependencies..."
sudo apt remove --purge build-essential libcurl4-openssl-dev libssl-dev libc-ares-dev -y

echo "Cleaning up..."
sudo apt autoremove -y
sudo apt autoclean

echo "Removing project dependencies..."
rm -rf node_modules vendor

echo "Cleanup complete!"
echo "Run 'docker system prune -a' to also clean Docker (affects all projects)"
```

Run it with:
```bash
chmod +x cleanup-dev.sh
./cleanup-dev.sh
```

---

## Quick Reference Card

```bash
# Start everything
docker-compose up -d postgres redis keycloak && php artisan octane:start --watch

# Stop everything  
Ctrl+C (in Octane terminal) && docker-compose down

# Rebuild frontend only
npm run build

# Full reset
php artisan migrate:fresh --seed && php artisan optimize:clear
```
