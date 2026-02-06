# Marketplace as a Service (MaaS) – Multi-tenant with DB per tenant

The application supports **Super Admin** onboarding of marketplaces (tenants). Each approved marketplace gets a **separate database** for full data segregation and isolation.

## Overview

- **Platform (central) database**: Stores `marketplaces` and `marketplace_kyc` (connection name: `platform`). Same DB as default unless you set `DB_PLATFORM_*` env vars.
- **Tenant databases**: One per approved marketplace. Created on KYC approval; contain vendors, products, orders, etc. (same schema as the main app).
- **Super Admin**: Role `super_admin`. Can onboard marketplaces, complete/review KYC, approve (provisions tenant DB) or reject.

## Setup

### 1. Platform connection

The `platform` connection in `config/database.php` points to the central DB (by default the same as `DB_*`). To use a dedicated platform DB:

```env
DB_PLATFORM_HOST=127.0.0.1
DB_PLATFORM_DATABASE=pcommerce_platform
DB_PLATFORM_USERNAME=root
DB_PLATFORM_PASSWORD=secret
# For PostgreSQL also set:
DB_PLATFORM_CONNECTION=pgsql
DB_PLATFORM_PORT=5432
```

### 2. Run migrations

Run migrations so the platform tables exist (on the `platform` connection):

```bash
php artisan migrate
```

Migrations that create `marketplaces` and `marketplace_kyc` use `Schema::connection('platform')` and run on the platform DB. All other migrations run on the default connection (your first/primary tenant DB).

### 3. Keycloak: Super Admin role

Add a realm or client role `super_admin` and assign it to the Super Admin user(s). The app uses `role:super_admin` middleware for `/super-admin/*` routes.

## Flow

1. **Super Admin** creates a marketplace: name, slug, email, domain. A draft KYC record is created.
2. KYC documents can be uploaded (via KYC form; document upload UI can be added). KYC is submitted (status `pending`).
3. Super Admin **starts review** (status `under_review`), then either:
   - **Approve**: KYC is approved; the service creates a new database for the tenant, runs migrations on it, and stores the tenant connection config on the marketplace. Status → `approved`.
   - **Reject**: KYC and marketplace are rejected; rejection reason stored.
4. **Tenant resolution**: When a request hits a tenant (e.g. subdomain `acme.pcommerce.com` or `X-Tenant: acme` header), `SetTenantConnection` middleware resolves the marketplace and sets the default DB connection to that tenant’s database so all Eloquent queries are isolated.

## Tenant database provisioning

- **MySQL/MariaDB**: `CREATE DATABASE` with name `pcommerce_tenant_{slug}` (configurable via `database.tenant_prefix`).
- **PostgreSQL**: `CREATE DATABASE` with same naming.
- Credentials are the same as the default DB connection (same user/host); only the database name changes.
- Encrypted password is stored in `marketplaces.db_password_encrypted` for runtime connection registration.

## Data isolation

- **Platform** models (`Marketplace`, `MarketplaceKyc`) use `protected $connection = 'platform'` and always read/write the central DB.
- **Tenant** models (Vendor, Product, Order, etc.) use the default connection, which is switched to the tenant’s connection by `SetTenantConnection` when a tenant is resolved.
- Subdomain or `X-Tenant` header must match an approved marketplace slug; otherwise the default connection is left unchanged (e.g. primary tenant or no tenant).

## Routes

| Route | Description |
|-------|-------------|
| `GET /super-admin` | Super Admin dashboard |
| `GET /super-admin/marketplaces` | List marketplaces |
| `GET /super-admin/marketplaces/create` | Onboard new marketplace form |
| `POST /super-admin/marketplaces` | Create marketplace + draft KYC |
| `GET /super-admin/marketplaces/{id}` | Marketplace detail |
| `GET /super-admin/marketplaces/{id}/kyc` | KYC review (documents, approve/reject) |
| `POST .../kyc/start-review` | Set KYC to under review |
| `POST .../kyc/approve` | Approve KYC and provision tenant DB |
| `POST .../kyc/reject` | Reject KYC |

All require authentication and role `super_admin`.
