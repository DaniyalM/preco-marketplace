# Getting Started with P-Commerce

This guide will help you run the P-Commerce application locally and start interacting with it.

## Prerequisites

- **Docker** and **Docker Compose** installed
- **Git** (for cloning the repository)
- At least **4GB RAM** available for Docker

## Quick Start

### 1. Clone and Navigate

```bash
cd /path/to/p-com
```

### 2. Create Environment File

```bash
cp .env.example .env
```

Edit `.env` and set your secrets:

```env
APP_KEY=base64:your-generated-key-here
KEYCLOAK_CLIENT_SECRET=your-client-secret
DB_PASSWORD=your-secure-password
```

Generate app key:

```bash
# Run this after containers are up
docker-compose exec app php artisan key:generate
```

### 3. Start All Services

```bash
# Start all services (app, mysql, redis, keycloak, nginx, ssr, worker)
docker-compose up -d

# Watch the logs (optional)
docker-compose logs -f
```

Wait for all services to be healthy (1-2 minutes on first run).

### 4. Run Database Migrations

```bash
docker-compose exec app php artisan migrate
```

### 5. Seed Test Data (Optional)

```bash
docker-compose exec app php artisan db:seed
```

## Accessing the Application

| Service | URL | Description |
|---------|-----|-------------|
| **Main Application** | http://localhost | Main storefront |
| **Keycloak Admin** | http://localhost:8080 | Identity provider admin |
| **Mailpit** | http://localhost:8025 | Email testing inbox |
| **phpMyAdmin** | http://localhost:8081 | Database management (if enabled) |

## Test User Accounts

The Keycloak realm includes pre-configured test users:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@example.com | admin123 |
| **Vendor** | vendor@example.com | vendor123 |
| **Customer** | customer@example.com | customer123 |

> **Note:** These users are created when you import the Keycloak realm. If they don't exist, import the realm file manually.

## Setting Up Keycloak (First Time)

### Option A: Auto-Import (Recommended)

The realm is auto-imported on first container startup via the `docker-compose.yml` configuration.

### Option B: Manual Import

1. Go to http://localhost:8080
2. Login with `admin` / `admin`
3. Click **Create Realm** → **Import**
4. Select `realms/pcommerce.json`
5. Click **Create**

### Get Client Secret

1. Go to Keycloak Admin → **Clients** → **pcommerce-app**
2. Go to **Credentials** tab
3. Copy the **Client Secret**
4. Add to your `.env` file:

```env
KEYCLOAK_CLIENT_SECRET=your-copied-secret
```

5. Restart the app:

```bash
docker-compose restart app
```

## Interacting with the Application

### As a Customer

1. **Browse Products**
   - Visit http://localhost/products
   - Browse categories at http://localhost/categories
   - View vendor stores at http://localhost/vendors

2. **Login**
   - Click **Login** → Redirects to Keycloak
   - Use `customer@example.com` / `customer123`
   - After login, you're redirected back

3. **Shopping**
   - View product details → Click **Add to Cart**
   - Visit http://localhost/cart to see your cart
   - Add items to wishlist with the heart icon

4. **Profile**
   - View your profile at http://localhost/profile
   - Manage addresses at http://localhost/profile/addresses

### As a Vendor

1. **Login as Vendor**
   - Use `vendor@example.com` / `vendor123`

2. **Complete Onboarding** (if new vendor)
   - Navigate to http://localhost/vendor/onboarding
   - Fill out:
     - **Basic Info**: Business name, email, phone, type
     - **Address**: Full business address
     - **KYC Documents**: Upload ID documents (any image files for testing)
   - Submit and wait for admin approval

3. **After Approval** - Access Vendor Dashboard
   - http://localhost/vendor - Dashboard with stats
   - http://localhost/vendor/products - Manage products
   - http://localhost/vendor/orders - View orders

4. **Create Products**
   - Go to http://localhost/vendor/products
   - Click **Create Product**
   - Fill form with:
     - Name, description, category
     - Base price, compare price
     - Upload images
     - Set stock quantity
     - Choose status (Draft/Active)
   - Submit → Product created

5. **Add Product Variants**
   - View product → Click **Add Variants**
   - Define options (Size: S, M, L; Color: Red, Blue)
   - Set price/stock for each combination
   - Submit

### As an Admin

1. **Login as Admin**
   - Use `admin@example.com` / `admin123`

2. **Admin Dashboard**
   - http://localhost/admin - Overview

3. **Manage Vendors**
   - http://localhost/admin/vendors - List all vendors
   - Click on vendor → View details
   - Actions: Approve, Reject, Suspend, Update Commission

4. **Review KYC**
   - http://localhost/admin/kyc - Pending KYC submissions
   - Click on submission → Review documents
   - Click **Start Review** → **Approve** or **Reject**

5. **Manage Categories/Products**
   - http://localhost/admin/categories
   - http://localhost/admin/products
   - http://localhost/admin/orders

## API Endpoints

### Public API (No Auth Required)

```bash
# List products
curl http://localhost/api/public/products

# Get product details
curl http://localhost/api/public/products/product-slug

# List categories
curl http://localhost/api/public/categories

# List vendors
curl http://localhost/api/public/vendors
```

### Authenticated API (Cookie Auth)

After logging in through the browser, cookies are set automatically:

```bash
# View cart
curl http://localhost/api/cart --cookie "access_token=..."

# Add to cart
curl -X POST http://localhost/api/cart/items \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 2}' \
  --cookie "access_token=..."
```

## Useful Commands

### Docker Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f app
docker-compose logs -f worker
docker-compose logs -f keycloak

# Restart specific service
docker-compose restart app

# Rebuild after code changes
docker-compose up -d --build app
```

### Laravel Commands

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Run seeders
docker-compose exec app php artisan db:seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Generate key
docker-compose exec app php artisan key:generate

# View routes
docker-compose exec app php artisan route:list
```

### Queue Management

```bash
# Check queue worker logs
docker-compose logs -f worker

# View failed jobs
docker-compose exec app php artisan queue:failed

# Retry failed jobs
docker-compose exec app php artisan queue:retry all

# Clear failed jobs
docker-compose exec app php artisan queue:flush
```

### Database

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u root -p pcommerce

# Fresh migration (drops all tables)
docker-compose exec app php artisan migrate:fresh

# Run specific seeder
docker-compose exec app php artisan db:seed --class=ProductSeeder
```

## Checking Email Notifications

All emails are captured by Mailpit in development:

1. Go to http://localhost:8025
2. View sent emails (vendor approvals, order confirmations, etc.)

## Troubleshooting

### App Not Starting

```bash
# Check logs
docker-compose logs app

# Ensure dependencies are installed
docker-compose exec app composer install
```

### Keycloak Not Ready

```bash
# Check Keycloak logs
docker-compose logs keycloak

# Wait for health check
docker-compose ps  # Check "healthy" status
```

### Database Connection Error

```bash
# Ensure MySQL is running
docker-compose ps mysql

# Check credentials in .env match docker-compose.yml
```

### SSR Not Working

```bash
# Check SSR container
docker-compose logs ssr

# Rebuild SSR
docker-compose exec app npm run build:ssr
docker-compose restart ssr
```

### Clear All and Start Fresh

```bash
# Stop and remove everything
docker-compose down -v

# Remove build cache
docker system prune -f

# Start fresh
docker-compose up -d --build
docker-compose exec app php artisan migrate:fresh --seed
```

## Development Workflow

### Making Code Changes

1. **Backend Changes** (PHP)
   - Edit files in `app/`, `routes/`, `config/`
   - Changes are reflected immediately with Octane (auto-reload in dev)

2. **Frontend Changes** (Vue/TS)
   - Run Vite in dev mode:
     ```bash
     docker-compose exec app npm run dev
     ```
   - Or use hot reload in `docker-compose.dev.yml`

3. **Database Changes**
   - Create migration:
     ```bash
     docker-compose exec app php artisan make:migration create_reviews_table
     ```
   - Run migrations:
     ```bash
     docker-compose exec app php artisan migrate
     ```

### Using Development Compose

For a better development experience with hot reload:

```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

This enables:
- Hot module replacement for Vue
- Xdebug for PHP debugging
- PHPMyAdmin at :8081
- Redis Commander at :8082
