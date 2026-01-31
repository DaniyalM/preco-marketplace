<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync product data to search index.
 * 
 * Dispatched when product is created/updated to keep
 * search index in sync without blocking the request.
 */
class SyncProductSearchIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Product $product,
        public string $action = 'upsert'
    ) {
        $this->onQueue('search');
    }

    public function handle(): void
    {
        Log::info("Syncing product {$this->product->id} to search index (action: {$this->action})");

        if ($this->action === 'delete') {
            $this->deleteFromIndex();
        } else {
            $this->upsertToIndex();
        }
    }

    protected function upsertToIndex(): void
    {
        // Prepare searchable data
        $data = [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'description' => $this->product->description,
            'short_description' => $this->product->short_description,
            'sku' => $this->product->sku,
            'category' => $this->product->category?->name,
            'vendor' => $this->product->vendor?->business_name,
            'price' => $this->product->base_price,
            'in_stock' => $this->product->isInStock(),
            'rating' => $this->product->average_rating,
            'tags' => $this->product->meta['tags'] ?? [],
        ];

        // TODO: Implement your search engine integration
        // Examples:
        // - Meilisearch: $this->meilisearch->index('products')->addDocuments([$data]);
        // - Algolia: Product::search()->update($this->product);
        // - Elasticsearch: $this->elastic->index(['index' => 'products', 'body' => $data]);

        Log::info("Product {$this->product->id} synced to search index");
    }

    protected function deleteFromIndex(): void
    {
        // TODO: Implement delete from search engine
        Log::info("Product {$this->product->id} deleted from search index");
    }
}
