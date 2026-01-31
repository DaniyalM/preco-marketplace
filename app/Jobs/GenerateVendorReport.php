<?php

namespace App\Jobs;

use App\Models\Vendor;
use App\Models\OrderItem;
use App\Notifications\VendorReportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Generate sales/payout report for vendor.
 * 
 * Heavy operation that queries and aggregates order data,
 * generates PDF/CSV, and emails to vendor.
 */
class GenerateVendorReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        public Vendor $vendor,
        public string $reportType,
        public string $startDate,
        public string $endDate,
        public string $format = 'csv'
    ) {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        Log::info("Generating {$this->reportType} report for vendor {$this->vendor->id}");

        $data = $this->gatherReportData();
        $filePath = $this->generateReport($data);

        // Notify vendor
        Notification::route('mail', $this->vendor->email)
            ->notify(new VendorReportReady(
                $this->vendor,
                $this->reportType,
                $filePath
            ));

        Log::info("Report generated for vendor {$this->vendor->id}: {$filePath}");
    }

    protected function gatherReportData(): array
    {
        $query = OrderItem::where('vendor_id', $this->vendor->id)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['order:id,order_number,status,payment_status,created_at', 'product:id,name,sku']);

        return match ($this->reportType) {
            'sales' => $this->getSalesData($query),
            'payout' => $this->getPayoutData($query),
            'products' => $this->getProductsData($query),
            default => [],
        };
    }

    protected function getSalesData($query): array
    {
        return [
            'summary' => [
                'total_orders' => $query->count(),
                'total_revenue' => $query->sum('total'),
                'total_payout' => $query->sum('vendor_payout'),
                'total_commission' => $query->sum('platform_commission'),
            ],
            'items' => $query->get()->map(fn ($item) => [
                'order_number' => $item->order->order_number,
                'date' => $item->created_at->toDateString(),
                'product' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'payout' => $item->vendor_payout,
                'status' => $item->fulfillment_status,
            ])->toArray(),
        ];
    }

    protected function getPayoutData($query): array
    {
        return $query->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->get()
            ->map(fn ($item) => [
                'order_number' => $item->order->order_number,
                'date' => $item->created_at->toDateString(),
                'product' => $item->product_name,
                'total' => $item->total,
                'commission_rate' => $item->commission_rate,
                'commission' => $item->platform_commission,
                'payout' => $item->vendor_payout,
            ])
            ->toArray();
    }

    protected function getProductsData($query): array
    {
        return $query->selectRaw('product_id, product_name, SUM(quantity) as total_sold, SUM(total) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->get()
            ->toArray();
    }

    protected function generateReport(array $data): string
    {
        $filename = sprintf(
            'reports/vendor-%d/%s-%s-to-%s.%s',
            $this->vendor->id,
            $this->reportType,
            $this->startDate,
            $this->endDate,
            $this->format
        );

        if ($this->format === 'csv') {
            $content = $this->generateCsv($data);
        } else {
            $content = json_encode($data, JSON_PRETTY_PRINT);
        }

        Storage::disk('private')->put($filename, $content);

        return $filename;
    }

    protected function generateCsv(array $data): string
    {
        $items = $data['items'] ?? $data;
        
        if (empty($items)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, array_keys($items[0]));
        
        // Data rows
        foreach ($items as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to generate report for vendor {$this->vendor->id}", [
            'error' => $exception->getMessage(),
            'report_type' => $this->reportType,
        ]);
    }
}
