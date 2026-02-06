# Sales Stats & Reporting (PostgreSQL Materialized Views)

When using **PostgreSQL**, sales and reporting statistics are pre-calculated and stored in materialized views. All figures are based on **paid orders only** (`payment_status = 'paid'`) and exclude cancelled/refunded line items.

## Views

| View | Description |
|------|-------------|
| `mv_sales_daily` | One row per day: order count, revenue, units, commission, vendor payout |
| `mv_sales_monthly` | One row per month: same aggregates for time-series and YoY |
| `mv_vendor_sales_stats` | Per-vendor: orders, units sold, revenue, commission, payout, first/last order |
| `mv_product_sales_stats` | Per-product: orders, units sold, revenue, vendor payout, first/last sale |
| `mv_category_sales_stats` | Per-category: orders, units sold, revenue |
| `mv_platform_sales_summary` | Single row: platform-wide totals |

## Creating the views

Run migrations (PostgreSQL only):

```bash
php artisan migrate
```

The migration `2025_01_31_100000_create_sales_materialized_views.php` creates all views and unique indexes (needed for concurrent refresh).

## Refreshing the views

Data is **not** live; refresh to update after new orders or changes.

**Manual refresh:**

```bash
php artisan stats:refresh-views
```

**Scheduled refresh:** The view refresh is scheduled to run **hourly** in `routes/console.php`. Ensure the scheduler is running (e.g. cron or a scheduler container).

## Querying from Laravel

Use the DB facade or raw queries. These are read-only views (no Eloquent models by default).

```php
use Illuminate\Support\Facades\DB;

// Platform summary (single row)
$summary = DB::table('mv_platform_sales_summary')->first();

// Daily sales for the last 30 days
$daily = DB::table('mv_sales_daily')
    ->where('sale_date', '>=', now()->subDays(30))
    ->orderBy('sale_date')
    ->get();

// Vendor leaderboard by revenue
$vendors = DB::table('mv_vendor_sales_stats')
    ->orderByDesc('total_revenue')
    ->limit(10)
    ->get();

// Top products by units sold
$products = DB::table('mv_product_sales_stats')
    ->orderByDesc('units_sold')
    ->limit(20)
    ->get();

// Category performance
$categories = DB::table('mv_category_sales_stats')
    ->whereNotNull('category_id')
    ->orderByDesc('revenue')
    ->get();
```

## Column reference

- **mv_sales_daily:** `sale_date`, `order_count`, `total_revenue`, `total_units`, `total_commission`, `total_vendor_payout`
- **mv_sales_monthly:** `year`, `month`, `period_start`, `order_count`, `total_revenue`, `total_units`, `total_commission`, `total_vendor_payout`
- **mv_vendor_sales_stats:** `vendor_id`, `business_name`, `vendor_slug`, `vendor_status`, `order_count`, `total_units_sold`, `total_revenue`, `total_commission`, `total_vendor_payout`, `first_order_at`, `last_order_at`
- **mv_product_sales_stats:** `product_id`, `product_name`, `sku`, `vendor_id`, `category_id`, `order_count`, `units_sold`, `revenue`, `vendor_payout`, `first_sale_at`, `last_sale_at`
- **mv_category_sales_stats:** `category_id`, `category_name`, `category_slug`, `parent_id`, `order_count`, `units_sold`, `revenue`
- **mv_platform_sales_summary:** `id`, `total_orders`, `total_revenue`, `total_units_sold`, `total_commission`, `total_vendor_payout`, `first_order_at`, `last_order_at`

## Non-PostgreSQL

Migrations and `stats:refresh-views` are no-ops when the driver is not `pgsql`. Use normal Eloquent/query aggregates for MySQL or SQLite reporting.
