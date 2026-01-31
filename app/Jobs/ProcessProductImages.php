<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Process uploaded product images - resize, optimize, create thumbnails.
 * 
 * Offloaded to queue because image processing is CPU intensive
 * and shouldn't block the request.
 */
class ProcessProductImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        public Product $product,
        public array $imagePaths
    ) {
        $this->onQueue('images');
    }

    public function handle(): void
    {
        Log::info("Processing images for product {$this->product->id}");

        foreach ($this->imagePaths as $index => $path) {
            try {
                $this->processImage($path, $index === 0);
            } catch (\Exception $e) {
                Log::error("Failed to process image: {$path}", [
                    'error' => $e->getMessage(),
                    'product_id' => $this->product->id,
                ]);
            }
        }

        Log::info("Completed image processing for product {$this->product->id}");
    }

    protected function processImage(string $path, bool $isPrimary): void
    {
        $disk = Storage::disk('public');
        
        if (!$disk->exists($path)) {
            Log::warning("Image not found: {$path}");
            return;
        }

        $fullPath = $disk->path($path);
        $directory = dirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Create optimized main image (max 1200px)
        $mainImage = Image::read($fullPath);
        $mainImage->scaleDown(width: 1200, height: 1200);
        $mainImage->toWebp(quality: 85);
        $mainPath = "{$directory}/{$filename}_main.webp";
        $disk->put($mainPath, $mainImage->encode());

        // Create thumbnail (400px)
        $thumbImage = Image::read($fullPath);
        $thumbImage->cover(400, 400);
        $thumbImage->toWebp(quality: 80);
        $thumbPath = "{$directory}/{$filename}_thumb.webp";
        $disk->put($thumbPath, $thumbImage->encode());

        // Create small thumbnail (150px) for cart/lists
        $smallImage = Image::read($fullPath);
        $smallImage->cover(150, 150);
        $smallImage->toWebp(quality: 75);
        $smallPath = "{$directory}/{$filename}_small.webp";
        $disk->put($smallPath, $smallImage->encode());

        // Update or create product image record
        ProductImage::updateOrCreate(
            ['product_id' => $this->product->id, 'path' => $path],
            [
                'path' => $mainPath,
                'thumbnail_path' => $thumbPath,
                'small_path' => $smallPath,
                'is_primary' => $isPrimary,
            ]
        );

        // Optionally delete original (keep for now)
        // $disk->delete($path);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessProductImages job failed for product {$this->product->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
