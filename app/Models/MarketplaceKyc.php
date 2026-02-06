<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceKyc extends Model
{
    protected $connection = 'platform';

    protected $table = 'marketplace_kyc';

    protected $fillable = [
        'marketplace_id',
        'legal_name',
        'tax_id',
        'business_type',
        'id_type',
        'id_document_front',
        'id_document_back',
        'proof_of_address',
        'business_registration',
        'tax_certificate',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'admin_notes',
        'document_expiry_date',
        'is_resubmission',
        'submission_count',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'document_expiry_date' => 'date',
        'is_resubmission' => 'boolean',
        'submission_count' => 'integer',
    ];

    protected $hidden = [
        'tax_id',
    ];

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $this->marketplace->update(['status' => 'pending_kyc']);
    }

    public function startReview(): void
    {
        $this->update(['status' => 'under_review']);
        $this->marketplace->update(['status' => 'kyc_under_review']);
    }

    public function approve(string $reviewedBy, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy,
            'admin_notes' => $notes,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reviewedBy, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy,
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);
        $this->marketplace->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
