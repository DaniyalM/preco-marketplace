<?php

use App\Jobs\CleanupExpiredCarts;
use App\Jobs\SendLowStockAlert;
use App\Models\Product;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Commands
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Refresh PostgreSQL sales materialized views.
 * Run after bulk order/product changes or on a schedule (e.g. every 15 min or hourly).
 */
Artisan::command('stats:refresh-views', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->warn('Materialized views are only available with PostgreSQL. Skipping.');

        return 0;
    }

    $views = [
        'mv_sales_daily',
        'mv_sales_monthly',
        'mv_vendor_sales_stats',
        'mv_product_sales_stats',
        'mv_category_sales_stats',
        'mv_platform_sales_summary',
    ];

    foreach ($views as $view) {
        try {
            DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$view}");
            $this->info("Refreshed {$view}");
        } catch (\Throwable $e) {
            $this->error("Failed to refresh {$view}: {$e->getMessage()}");

            return 1;
        }
    }

    $this->info('All sales materialized views refreshed.');

    return 0;
})->purpose('Refresh PostgreSQL materialized views for sales stats and reporting');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| These tasks run in the background via the scheduler container.
| See docker-compose.yml scheduler service.
|
*/

// Cleanup expired carts (older than 30 days)
Schedule::job(new CleanupExpiredCarts(30))
    ->daily()
    ->at('03:00')
    ->name('cleanup-expired-carts')
    ->withoutOverlapping()
    ->onOneServer();

// Check for low stock products and send alerts
Schedule::call(function () {
    $lowStockProducts = Product::where('track_inventory', true)
        ->where('status', 'active')
        ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
        ->where('stock_quantity', '>', 0) // Not out of stock
        ->with('vendor')
        ->get();

    foreach ($lowStockProducts as $product) {
        // Only send once per day per product
        $cacheKey = "low_stock_alert:{$product->id}:" . now()->toDateString();
        
        if (!cache()->has($cacheKey)) {
            SendLowStockAlert::dispatch($product);
            cache()->put($cacheKey, true, now()->endOfDay());
        }
    }
})->daily()->at('09:00')->name('low-stock-alerts');

// Clear old failed jobs
Schedule::command('queue:flush')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->name('flush-failed-jobs');

// Prune old telescope entries (if installed)
// Schedule::command('telescope:prune --hours=48')->daily();

// Prune old activity logs
Schedule::command('activitylog:clean --days=30')
    ->weekly()
    ->sundays()
    ->at('01:00')
    ->name('clean-activity-log')
    ->withoutOverlapping();

// Cache optimization
Schedule::command('cache:prune-stale-tags')
    ->hourly()
    ->name('prune-stale-cache');

// Queue monitoring - restart workers if memory usage is high
Schedule::command('queue:restart')
    ->everyFifteenMinutes()
    ->name('queue-health-check')
    ->when(function () {
        // Only restart if memory usage is above 80%
        return memory_get_usage(true) / 1024 / 1024 > 400; // 400MB threshold
    });

// Refresh PostgreSQL sales/reporting materialized views (PostgreSQL only)
Schedule::command('stats:refresh-views')
    ->hourly()
    ->name('refresh-sales-stats-views')
    ->withoutOverlapping(30)
    ->onOneServer();
