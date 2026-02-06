<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL materialized views for sales stats and reporting.
 * All stats are calculated from paid orders only (payment_status = 'paid').
 * Run only when using PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        // 1. Daily sales (for time-series and charts)
        DB::statement("
            CREATE MATERIALIZED VIEW mv_sales_daily AS
            SELECT
                (date_trunc('day', o.created_at AT TIME ZONE 'UTC'))::date AS sale_date,
                COUNT(DISTINCT o.id) AS order_count,
                COALESCE(SUM(o.total), 0) AS total_revenue,
                COALESCE(SUM(oi.quantity), 0)::bigint AS total_units,
                COALESCE(SUM(oi.commission_amount), 0) AS total_commission,
                COALESCE(SUM(oi.vendor_payout), 0) AS total_vendor_payout
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL
            WHERE o.payment_status = 'paid'
              AND oi.fulfillment_status NOT IN ('cancelled', 'refunded')
            GROUP BY (date_trunc('day', o.created_at AT TIME ZONE 'UTC'))::date
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_sales_daily (sale_date)');

        // 2. Monthly sales (for reporting and YoY); subquery so GROUP BY has one expression
        DB::statement("
            CREATE MATERIALIZED VIEW mv_sales_monthly AS
            SELECT
                EXTRACT(YEAR FROM period_start)::int AS year,
                EXTRACT(MONTH FROM period_start)::int AS month,
                period_start,
                order_count,
                total_revenue,
                total_units,
                total_commission,
                total_vendor_payout
            FROM (
                SELECT
                    (date_trunc('month', o.created_at AT TIME ZONE 'UTC'))::date AS period_start,
                    COUNT(DISTINCT o.id) AS order_count,
                    COALESCE(SUM(o.total), 0) AS total_revenue,
                    COALESCE(SUM(oi.quantity), 0)::bigint AS total_units,
                    COALESCE(SUM(oi.commission_amount), 0) AS total_commission,
                    COALESCE(SUM(oi.vendor_payout), 0) AS total_vendor_payout
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL
                WHERE o.payment_status = 'paid'
                  AND oi.fulfillment_status NOT IN ('cancelled', 'refunded')
                GROUP BY (date_trunc('month', o.created_at AT TIME ZONE 'UTC'))::date
            ) sub
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_sales_monthly (year, month)');

        // 3. Vendor sales stats (per-vendor reporting; only paid order items)
        DB::statement("
            CREATE MATERIALIZED VIEW mv_vendor_sales_stats AS
            SELECT
                v.id AS vendor_id,
                v.business_name,
                v.slug AS vendor_slug,
                v.status AS vendor_status,
                COUNT(DISTINCT paid.order_id) AS order_count,
                COALESCE(SUM(paid.quantity), 0)::bigint AS total_units_sold,
                COALESCE(SUM(paid.total), 0) AS total_revenue,
                COALESCE(SUM(paid.commission_amount), 0) AS total_commission,
                COALESCE(SUM(paid.vendor_payout), 0) AS total_vendor_payout,
                MIN(paid.order_created_at) AS first_order_at,
                MAX(paid.order_created_at) AS last_order_at
            FROM vendors v
            LEFT JOIN (
                SELECT
                    oi.vendor_id,
                    oi.order_id,
                    oi.quantity,
                    oi.total,
                    oi.commission_amount,
                    oi.vendor_payout,
                    o.created_at AS order_created_at
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL AND o.payment_status = 'paid'
                WHERE oi.fulfillment_status NOT IN ('cancelled', 'refunded')
            ) paid ON paid.vendor_id = v.id
            WHERE v.deleted_at IS NULL
            GROUP BY v.id, v.business_name, v.slug, v.status
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_vendor_sales_stats (vendor_id)');

        // 4. Product sales stats (per-product performance; only paid order items)
        DB::statement("
            CREATE MATERIALIZED VIEW mv_product_sales_stats AS
            SELECT
                p.id AS product_id,
                p.name AS product_name,
                p.sku,
                p.vendor_id,
                p.category_id,
                COUNT(DISTINCT paid.order_id) AS order_count,
                COALESCE(SUM(paid.quantity), 0)::bigint AS units_sold,
                COALESCE(SUM(paid.total), 0) AS revenue,
                COALESCE(SUM(paid.vendor_payout), 0) AS vendor_payout,
                MIN(paid.order_created_at) AS first_sale_at,
                MAX(paid.order_created_at) AS last_sale_at
            FROM products p
            LEFT JOIN (
                SELECT
                    oi.product_id,
                    oi.order_id,
                    oi.quantity,
                    oi.total,
                    oi.vendor_payout,
                    o.created_at AS order_created_at
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL AND o.payment_status = 'paid'
                WHERE oi.fulfillment_status NOT IN ('cancelled', 'refunded')
            ) paid ON paid.product_id = p.id
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.name, p.sku, p.vendor_id, p.category_id
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_product_sales_stats (product_id)');

        // 5. Category sales stats (per-category performance; only paid order items)
        DB::statement("
            CREATE MATERIALIZED VIEW mv_category_sales_stats AS
            SELECT
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                c.parent_id,
                COUNT(DISTINCT paid.order_id) AS order_count,
                COALESCE(SUM(paid.quantity), 0)::bigint AS units_sold,
                COALESCE(SUM(paid.total), 0) AS revenue
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id AND p.deleted_at IS NULL
            LEFT JOIN (
                SELECT
                    oi.product_id,
                    oi.order_id,
                    oi.quantity,
                    oi.total
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL AND o.payment_status = 'paid'
                WHERE oi.fulfillment_status NOT IN ('cancelled', 'refunded')
            ) paid ON paid.product_id = p.id
            GROUP BY c.id, c.name, c.slug, c.parent_id
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_category_sales_stats (category_id)');

        // 6. Platform summary (single-row overall stats)
        DB::statement("
            CREATE MATERIALIZED VIEW mv_platform_sales_summary AS
            SELECT
                1 AS id,
                COUNT(DISTINCT o.id) AS total_orders,
                COALESCE(SUM(o.total), 0) AS total_revenue,
                COALESCE(SUM(oi.quantity), 0)::bigint AS total_units_sold,
                COALESCE(SUM(oi.commission_amount), 0) AS total_commission,
                COALESCE(SUM(oi.vendor_payout), 0) AS total_vendor_payout,
                MIN(o.created_at) AS first_order_at,
                MAX(o.created_at) AS last_order_at
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id AND o.deleted_at IS NULL
            WHERE o.payment_status = 'paid'
              AND oi.fulfillment_status NOT IN ('cancelled', 'refunded')
        ");
        DB::statement('CREATE UNIQUE INDEX ON mv_platform_sales_summary (id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_platform_sales_summary');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_category_sales_stats');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_product_sales_stats');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_vendor_sales_stats');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_sales_monthly');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_sales_daily');
    }
};
