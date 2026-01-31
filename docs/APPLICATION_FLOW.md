# P-Commerce Application Flow Diagram

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                  FRONTEND                                        │
│                        Vue 3 + Inertia + Shadcn UI                              │
└─────────────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                               NGINX REVERSE PROXY                                │
│                          (Port 80/443 → Routes traffic)                         │
└─────────────────────────────────────────────────────────────────────────────────┘
                          │                              │
                          ▼                              ▼
┌─────────────────────────────────────┐   ┌─────────────────────────────────────┐
│      LARAVEL OCTANE (Port 8000)     │   │      KEYCLOAK (Port 8080)           │
│   ┌─────────────────────────────┐   │   │  ┌─────────────────────────────┐    │
│   │ VerifyKeycloakToken         │   │   │  │ Realm: pcommerce            │    │
│   │ Middleware (JWT Validation) │◄──┼───┼──│ Client: pcommerce-app       │    │
│   └─────────────────────────────┘   │   │  │ Roles: admin,vendor,customer│    │
│              │                      │   │  └─────────────────────────────┘    │
│              ▼                      │   └─────────────────────────────────────┘
│   ┌─────────────────────────────┐   │
│   │ Controllers                 │   │
│   │ - Web (Inertia)            │   │
│   │ - API (JSON)               │   │
│   └─────────────────────────────┘   │
│              │                      │
│              ▼                      │
│   ┌─────────────────────────────┐   │
│   │ Jobs → Queue (Redis)        │───┼───────┐
│   └─────────────────────────────┘   │       │
└─────────────────────────────────────┘       │
                          │                    │
                          ▼                    ▼
┌─────────────────────────────────────┐   ┌─────────────────────────────────────┐
│         MYSQL DATABASE              │   │          QUEUE WORKER               │
│  - vendors, products, orders        │   │  - Process background jobs          │
│  - carts, wishlists, kyc            │   │  - Send notifications               │
└─────────────────────────────────────┘   │  - Process images                   │
                                          └─────────────────────────────────────┘
                                                          │
                                                          ▼
                                          ┌─────────────────────────────────────┐
                                          │           MAILPIT (Dev)             │
                                          │     Email Testing @ :8025           │
                                          └─────────────────────────────────────┘
```

---

## Authentication Flow (Stateless JWT)

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  Browser │     │  Laravel │     │ Keycloak │     │  Redis   │
└────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘
     │                │                │                │
     │  GET /login    │                │                │
     │───────────────►│                │                │
     │                │                │                │
     │  302 Redirect  │                │                │
     │◄───────────────│                │                │
     │                │                │                │
     │  Keycloak Login Page            │                │
     │────────────────────────────────►│                │
     │                │                │                │
     │  User enters credentials        │                │
     │────────────────────────────────►│                │
     │                │                │                │
     │  302 + auth code                │                │
     │◄────────────────────────────────│                │
     │                │                │                │
     │  GET /auth/callback?code=xxx    │                │
     │───────────────►│                │                │
     │                │                │                │
     │                │  Exchange code │                │
     │                │───────────────►│                │
     │                │                │                │
     │                │  Access + Refresh Token         │
     │                │◄───────────────│                │
     │                │                │                │
     │                │  Cache public key               │
     │                │────────────────────────────────►│
     │                │                │                │
     │  Set HTTP-only cookies (access_token, refresh_token)
     │◄───────────────│                │                │
     │                │                │                │
     │  Subsequent requests with cookies                │
     │───────────────►│                │                │
     │                │  Validate JWT  │                │
     │                │  (from cookie) │                │
     │                │  Extract: user_id, tenant_id, roles
     │                │                │                │
     │  Response      │                │                │
     │◄───────────────│                │                │
```

### Test Authentication

```bash
# 1. Start the application
docker-compose up -d

# 2. Access Keycloak Admin Console
# URL: http://localhost:8080
# Username: admin
# Password: admin

# 3. Test users (defined in realm config):
#    - admin@example.com / admin123 (admin role)
#    - vendor@example.com / vendor123 (vendor role)
#    - customer@example.com / customer123 (customer role)

# 4. Access the application
# URL: http://localhost
# Click "Login" → Redirects to Keycloak → Login → Redirects back
```

---

## User Roles & Access Matrix

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              ROLE-BASED ACCESS                                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐       │
│  │   PUBLIC    │    │  CUSTOMER   │    │   VENDOR    │    │    ADMIN    │       │
│  │  (No Auth)  │    │   (Auth)    │    │   (Auth)    │    │   (Auth)    │       │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘    └──────┬──────┘       │
│         │                  │                  │                  │               │
│         ▼                  ▼                  ▼                  ▼               │
│  ┌────────────┐     ┌────────────┐     ┌────────────┐     ┌────────────┐        │
│  │ Browse     │     │ All Public │     │ All Public │     │ All Public │        │
│  │ Products   │     │ + Cart     │     │ + Cart     │     │ + Cart     │        │
│  │ Categories │     │ + Wishlist │     │ + Wishlist │     │ + Wishlist │        │
│  │ Vendors    │     │ + Checkout │     │ + Onboard  │     │ + Manage   │        │
│  │            │     │ + Orders   │     │ + Products │     │   Vendors  │        │
│  │            │     │ + Profile  │     │ + Dashboard│     │ + KYC      │        │
│  │            │     │            │     │ + Orders   │     │ + All Data │        │
│  └────────────┘     └────────────┘     └────────────┘     └────────────┘        │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Vendor Onboarding Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           VENDOR ONBOARDING JOURNEY                              │
└─────────────────────────────────────────────────────────────────────────────────┘

  ┌─────────┐      ┌─────────┐      ┌─────────┐      ┌─────────┐      ┌─────────┐
  │  Login  │      │  Basic  │      │ Address │      │   KYC   │      │ Pending │
  │   as    │─────►│  Info   │─────►│  Form   │─────►│Documents│─────►│ Review  │
  │ Vendor  │      │         │      │         │      │         │      │         │
  └─────────┘      └─────────┘      └─────────┘      └─────────┘      └─────────┘
                        │                │                │                │
                        ▼                ▼                ▼                ▼
                   ┌─────────┐      ┌─────────┐      ┌─────────┐      ┌─────────┐
                   │Business │      │ Street  │      │ID Front │      │  Wait   │
                   │  Name   │      │ Address │      │ID Back  │      │  for    │
                   │ Email   │      │ City    │      │Proof of │      │ Admin   │
                   │ Phone   │      │ State   │      │ Address │      │Approval │
                   │  Type   │      │ Country │      │Business │      │         │
                   │         │      │ ZIP     │      │ Reg.    │      │         │
                   └─────────┘      └─────────┘      └─────────┘      └─────────┘
                                                           │
                                                           ▼
                                                    ┌─────────────┐
                                                    │ Background  │
                                                    │    Job:     │
                                                    │ProcessKyc   │
                                                    │Documents    │
                                                    └─────────────┘

                                   ADMIN REVIEW
  ┌───────────────────────────────────────────────────────────────────────────┐
  │                                                                           │
  │   ┌─────────────┐          ┌─────────────┐          ┌─────────────┐      │
  │   │   APPROVE   │          │   REJECT    │          │   SUSPEND   │      │
  │   │             │          │             │          │             │      │
  │   │ vendor.status│         │ vendor.status│         │ vendor.status│     │
  │   │ = 'approved' │         │ = 'rejected' │         │ = 'suspended'│     │
  │   │             │          │ + reason    │          │ + reason    │      │
  │   └──────┬──────┘          └──────┬──────┘          └──────┬──────┘      │
  │          │                        │                        │              │
  │          ▼                        ▼                        ▼              │
  │   ┌─────────────┐          ┌─────────────┐          ┌─────────────┐      │
  │   │ Email:      │          │ Email:      │          │ Email:      │      │
  │   │VendorApproved│         │VendorRejected│         │VendorSuspended│    │
  │   └─────────────┘          └─────────────┘          └─────────────┘      │
  │                                                                           │
  └───────────────────────────────────────────────────────────────────────────┘
```

### Test Vendor Onboarding

```bash
# 1. Login as vendor (vendor@example.com / vendor123)
# 2. Navigate to /vendor/onboarding
# 3. Fill out:
#    - Basic Info: Business name, email, phone, type
#    - Address: Full business address
#    - KYC: Upload ID documents (any image files work for testing)
# 4. Submit and wait for admin approval
# 5. Login as admin (admin@example.com / admin123)
# 6. Navigate to /admin/kyc
# 7. Review and approve the KYC submission
# 8. Login as vendor again → Access vendor dashboard
```

---

## Product Management Flow (Vendor)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         VENDOR PRODUCT MANAGEMENT                                │
└─────────────────────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────────────┐
                    │        VENDOR DASHBOARD             │
                    │         /vendor                     │
                    └──────────────────┬──────────────────┘
                                       │
           ┌───────────────────────────┼───────────────────────────┐
           │                           │                           │
           ▼                           ▼                           ▼
   ┌───────────────┐           ┌───────────────┐           ┌───────────────┐
   │   Products    │           │    Orders     │           │   Settings    │
   │/vendor/products│           │/vendor/orders │           │/vendor/settings│
   └───────┬───────┘           └───────────────┘           └───────────────┘
           │
     ┌─────┴─────┐
     ▼           ▼
┌─────────┐  ┌─────────┐
│  List   │  │ Create  │
│Products │  │ Product │
└────┬────┘  └────┬────┘
     │            │
     │            ▼
     │     ┌────────────────────────────────────────┐
     │     │          CREATE PRODUCT FORM           │
     │     │                                        │
     │     │  ┌─────────────────────────────────┐  │
     │     │  │ Basic Info                      │  │
     │     │  │ - Name, Description, Category   │  │
     │     │  │ - Base Price, Compare Price     │  │
     │     │  │ - SKU, Product Type             │  │
     │     │  └─────────────────────────────────┘  │
     │     │                                        │
     │     │  ┌─────────────────────────────────┐  │
     │     │  │ Inventory                       │  │
     │     │  │ - Stock Quantity                │  │
     │     │  │ - Low Stock Threshold           │  │
     │     │  │ - Track Inventory               │  │
     │     │  └─────────────────────────────────┘  │
     │     │                                        │
     │     │  ┌─────────────────────────────────┐  │
     │     │  │ Images (Multiple)               │  │──────┐
     │     │  │ - Primary Image                 │  │      │
     │     │  │ - Gallery Images                │  │      │
     │     │  └─────────────────────────────────┘  │      │
     │     │                                        │      │
     │     │  ┌─────────────────────────────────┐  │      │
     │     │  │ Variants (Optional)             │  │      ▼
     │     │  │ - Size: S, M, L, XL             │  │  ┌─────────┐
     │     │  │ - Color: Red, Blue, Green       │  │  │  Job:   │
     │     │  │ - Each with own price/stock     │  │  │ Process │
     │     │  └─────────────────────────────────┘  │  │ Product │
     │     │                                        │  │ Images  │
     │     └────────────────────────────────────────┘  └─────────┘
     │                      │
     │                      ▼
     │              ┌───────────────┐
     │              │  Job: Sync    │
     │              │  Search Index │
     │              └───────────────┘
     │
     └────►┌─────────────────────────────────────────┐
           │           PRODUCT DETAIL VIEW           │
           │                                         │
           │  Edit Product                           │
           │  Add Variants                           │
           │  View Stats (views, sold, rating)       │
           │  Delete Product                         │
           │                                         │
           └─────────────────────────────────────────┘
```

### Test Product Management

```bash
# Prerequisite: Vendor must be approved

# 1. Login as approved vendor
# 2. Navigate to /vendor/products
# 3. Click "Create Product"
# 4. Fill form:
#    - Name: "Test Product"
#    - Description: "A test product"
#    - Category: Select any
#    - Base Price: 99.99
#    - Upload images (any image files)
#    - Status: Active
# 5. Submit → Product created
# 6. Add variants (optional):
#    - Options: Size (S, M, L), Color (Red, Blue)
#    - Set price/stock for each variant combination
# 7. View product in public store: /products
```

---

## Customer Shopping Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           CUSTOMER SHOPPING JOURNEY                              │
└─────────────────────────────────────────────────────────────────────────────────┘

  ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
  │ Browse  │     │ Product │     │  Add to │     │  View   │     │Checkout │
  │Products │────►│ Details │────►│  Cart   │────►│  Cart   │────►│         │
  └─────────┘     └─────────┘     └─────────┘     └─────────┘     └─────────┘
       │                │               │               │               │
       ▼                ▼               ▼               ▼               ▼
  ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
  │/products│     │/products│     │ API:    │     │  /cart  │     │/checkout│
  │         │     │ /{slug} │     │POST /api│     │         │     │         │
  │ Filter  │     │         │     │/cart/   │     │ Update  │     │ Address │
  │ - Cat   │     │ Gallery │     │items    │     │ Quantity│     │ Payment │
  │ - Price │     │ Variants│     │         │     │ Remove  │     │ Confirm │
  │ - Vendor│     │ Reviews │     │         │     │         │     │         │
  └─────────┘     └─────────┘     └─────────┘     └─────────┘     └─────────┘
                        │                                               │
                        ▼                                               ▼
                  ┌───────────┐                                   ┌───────────┐
                  │ Wishlist  │                                   │   Order   │
                  │ Toggle    │                                   │  Created  │
                  │ API: POST │                                   │           │
                  │ /wishlist │                                   └─────┬─────┘
                  └───────────┘                                         │
                                                                        ▼
                                                            ┌───────────────────┐
                                                            │   Job: Process    │
                                                            │   Order Placed    │
                                                            │                   │
                                                            │ - Email customer  │
                                                            │ - Notify vendors  │
                                                            │ - Update stock    │
                                                            │ - Update stats    │
                                                            └───────────────────┘
```

### Test Customer Shopping

```bash
# 1. Browse products at /products (no auth required)
# 2. Click on a product to view details
# 3. Login as customer (customer@example.com / customer123)
# 4. Add product to cart
# 5. View cart at /cart
# 6. Update quantities, remove items
# 7. Proceed to checkout
# 8. Add to wishlist: /wishlist
```

---

## Admin Management Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            ADMIN MANAGEMENT                                      │
└─────────────────────────────────────────────────────────────────────────────────┘

                      ┌─────────────────────────────────┐
                      │        ADMIN DASHBOARD          │
                      │           /admin                │
                      └────────────────┬────────────────┘
                                       │
       ┌───────────────┬───────────────┼───────────────┬───────────────┐
       │               │               │               │               │
       ▼               ▼               ▼               ▼               ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│   Vendors   │ │     KYC     │ │ Categories  │ │  Products   │ │   Orders    │
│/admin/vendors│ │ /admin/kyc  │ │/admin/cat.  │ │/admin/prod. │ │/admin/orders│
└──────┬──────┘ └──────┬──────┘ └─────────────┘ └─────────────┘ └─────────────┘
       │               │
       ▼               ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                          VENDOR/KYC MANAGEMENT                                   │
│                                                                                  │
│  ┌───────────────────────────────────────────────────────────────────────────┐  │
│  │                           VENDOR DETAIL VIEW                              │  │
│  │                                                                           │  │
│  │  Vendor Info:                    Actions:                                 │  │
│  │  - Business Name                 ┌─────────────────┐                      │  │
│  │  - Email, Phone                  │ ✓ APPROVE       │──► Email Sent        │  │
│  │  - Business Type                 ├─────────────────┤                      │  │
│  │  - Address                       │ ✗ REJECT        │──► Email + Reason    │  │
│  │  - KYC Status                    ├─────────────────┤                      │  │
│  │  - Commission Rate               │ ⚠ SUSPEND       │──► Email + Reason    │  │
│  │                                  ├─────────────────┤                      │  │
│  │  KYC Documents:                  │ ★ TOGGLE FEAT.  │                      │  │
│  │  - ID Front/Back                 ├─────────────────┤                      │  │
│  │  - Proof of Address              │ % COMMISSION    │──► Update Rate       │  │
│  │  - Business Registration         └─────────────────┘                      │  │
│  │                                                                           │  │
│  └───────────────────────────────────────────────────────────────────────────┘  │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### Test Admin Functions

```bash
# 1. Login as admin (admin@example.com / admin123)
# 2. Navigate to /admin
# 3. View vendor list at /admin/vendors
# 4. View pending KYC at /admin/kyc
# 5. Review KYC submission:
#    - Start Review
#    - View documents
#    - Approve or Reject
# 6. Manage vendor:
#    - Update commission rate
#    - Toggle featured status
#    - Suspend if needed
```

---

## Background Jobs & Queue Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              QUEUE ARCHITECTURE                                  │
└─────────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────────┐
                              │   REDIS QUEUE   │
                              │                 │
                              │ ┌─────────────┐ │
                              │ │ high        │ │ ◄── Priority 1 (Urgent)
                              │ ├─────────────┤ │
                              │ │ orders      │ │ ◄── Priority 2
                              │ ├─────────────┤ │
                              │ │notifications│ │ ◄── Priority 3
                              │ ├─────────────┤ │
                              │ │ default     │ │ ◄── Priority 4
                              │ ├─────────────┤ │
                              │ │ images      │ │ ◄── Priority 5
                              │ ├─────────────┤ │
                              │ │ documents   │ │ ◄── Priority 6
                              │ ├─────────────┤ │
                              │ │ search      │ │ ◄── Priority 7
                              │ ├─────────────┤ │
                              │ │ reports     │ │ ◄── Priority 8
                              │ ├─────────────┤ │
                              │ │ cleanup     │ │ ◄── Priority 9
                              │ └─────────────┘ │
                              └────────┬────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
              ▼                        ▼                        ▼
    ┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
    │  WORKER (Main)  │      │ WORKER (Heavy)  │      │    SCHEDULER    │
    │                 │      │                 │      │                 │
    │ All queues      │      │ images          │      │ Cron tasks      │
    │ --timeout=300   │      │ documents       │      │ every minute    │
    │ --max-jobs=1000 │      │ reports         │      │                 │
    │                 │      │ --timeout=600   │      │ Dispatches:     │
    │                 │      │ --max-jobs=100  │      │ - Cart cleanup  │
    └─────────────────┘      └─────────────────┘      │ - Stock alerts  │
                                                       │ - Cache prune   │
                                                       └─────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                               JOB TYPES                                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  NOTIFICATIONS (queue: notifications)                                            │
│  ├── SendVendorApprovalNotification  ──► Email vendor on approve/reject          │
│  ├── SendKycStatusNotification       ──► Email vendor on KYC status change       │
│  └── SendLowStockAlert               ──► Email vendor when stock low             │
│                                                                                  │
│  ORDER PROCESSING (queue: orders)                                                │
│  └── ProcessOrderPlaced              ──► Confirm email, notify vendors, stock    │
│                                                                                  │
│  IMAGE PROCESSING (queue: images)                                                │
│  └── ProcessProductImages            ──► Resize, optimize, thumbnails            │
│                                                                                  │
│  DOCUMENT PROCESSING (queue: documents)                                          │
│  └── ProcessKycDocuments             ──► Validate, scan KYC uploads              │
│                                                                                  │
│  SEARCH INDEX (queue: search)                                                    │
│  └── SyncProductSearchIndex          ──► Sync product to Meilisearch/Algolia     │
│                                                                                  │
│  REPORTS (queue: reports)                                                        │
│  └── GenerateVendorReport            ──► Generate CSV/PDF sales reports          │
│                                                                                  │
│  CLEANUP (queue: cleanup)                                                        │
│  └── CleanupExpiredCarts             ──► Remove old abandoned carts              │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### Test Queue Processing

```bash
# 1. Check worker logs
docker-compose logs -f worker

# 2. View failed jobs
docker-compose exec app php artisan queue:failed

# 3. Retry failed jobs
docker-compose exec app php artisan queue:retry all

# 4. Monitor queues (if Horizon installed)
# http://localhost/horizon
```

---

## API Endpoints Summary

### Public API (No Auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/products` | List products |
| GET | `/api/public/products/{slug}` | Product details |
| GET | `/api/public/products/featured` | Featured products |
| GET | `/api/public/products/search` | Search products |
| GET | `/api/public/categories` | List categories |
| GET | `/api/public/categories/{slug}` | Category details |
| GET | `/api/public/categories/{slug}/products` | Products in category |
| GET | `/api/public/vendors` | List vendors |
| GET | `/api/public/vendors/{slug}` | Vendor details |
| GET | `/api/public/vendors/{slug}/products` | Vendor products |

### Authenticated API

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/user/profile` | Any | Current user profile |
| GET | `/api/cart` | Any | View cart |
| POST | `/api/cart/items` | Any | Add to cart |
| PATCH | `/api/cart/items/{id}` | Any | Update cart item |
| DELETE | `/api/cart/items/{id}` | Any | Remove from cart |
| DELETE | `/api/cart` | Any | Clear cart |
| GET | `/api/wishlist` | Any | View wishlist |
| POST | `/api/wishlist` | Any | Toggle wishlist item |
| DELETE | `/api/wishlist/{product}` | Any | Remove from wishlist |

---

## Testing Checklist

### Quick Start

```bash
# 1. Start all services
docker-compose up -d

# 2. Wait for services to be ready (check logs)
docker-compose logs -f

# 3. Run migrations
docker-compose exec app php artisan migrate

# 4. Import Keycloak realm (if not auto-imported)
# Access Keycloak admin: http://localhost:8080
# Import: realms/pcommerce.json

# 5. Access the application
# Main app: http://localhost
# Keycloak: http://localhost:8080
# Mailpit: http://localhost:8025 (for testing emails)
# phpMyAdmin: http://localhost:8081 (if enabled)
```

### Test Scenarios

- [ ] **Public browsing** - View products, categories, vendors without login
- [ ] **Authentication** - Login with each role (admin, vendor, customer)
- [ ] **Vendor onboarding** - Complete full onboarding flow
- [ ] **KYC review** - Admin reviews and approves KYC
- [ ] **Product creation** - Vendor creates product with images
- [ ] **Product variants** - Add size/color variants
- [ ] **Cart operations** - Add, update, remove items
- [ ] **Wishlist** - Add/remove products
- [ ] **Email notifications** - Check Mailpit for sent emails
- [ ] **Queue jobs** - Monitor worker logs for job processing
