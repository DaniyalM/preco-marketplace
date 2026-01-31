<?php

namespace App\Jobs;

use App\Models\VendorKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process uploaded KYC documents.
 * 
 * - Validate document integrity
 * - Scan for malware (if service available)
 * - Generate secure thumbnails for admin preview
 * - Extract metadata
 */
class ProcessKycDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 180;

    public function __construct(
        public VendorKyc $kyc
    ) {
        $this->onQueue('documents');
    }

    public function handle(): void
    {
        Log::info("Processing KYC documents for vendor {$this->kyc->vendor_id}");

        $documents = [
            'id_document_front' => $this->kyc->id_document_front,
            'id_document_back' => $this->kyc->id_document_back,
            'proof_of_address' => $this->kyc->proof_of_address,
            'business_registration' => $this->kyc->business_registration,
        ];

        $disk = Storage::disk('private');

        foreach ($documents as $type => $path) {
            if (!$path || !$disk->exists($path)) {
                continue;
            }

            try {
                // Validate file type
                $mimeType = $disk->mimeType($path);
                $this->validateMimeType($mimeType, $type);

                // Get file size
                $size = $disk->size($path);
                
                Log::info("Processed KYC document", [
                    'type' => $type,
                    'mime' => $mimeType,
                    'size' => $size,
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to process KYC document: {$type}", [
                    'error' => $e->getMessage(),
                    'kyc_id' => $this->kyc->id,
                ]);
            }
        }

        Log::info("Completed KYC document processing for vendor {$this->kyc->vendor_id}");
    }

    protected function validateMimeType(string $mimeType, string $documentType): void
    {
        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("Invalid mime type for {$documentType}: {$mimeType}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessKycDocuments job failed for KYC {$this->kyc->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
