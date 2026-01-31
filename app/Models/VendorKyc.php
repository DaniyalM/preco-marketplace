<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorKyc extends Model
{
    use HasFactory;

    protected $table = 'vendor_kyc';

    protected $fillable = [
        'vendor_id',
        'legal_name',
        'tax_id',
        'date_of_birth',
        'nationality',
        'id_type',
        'id_document_front',
        'id_document_back',
        'proof_of_address',
        'business_registration',
        'tax_certificate',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_routing_number',
        'bank_swift_code',
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
        'date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'document_expiry_date' => 'date',
        'is_resubmission' => 'boolean',
        'submission_count' => 'integer',
    ];

    protected $hidden = [
        'tax_id',
        'bank_account_number',
        'bank_routing_number',
    ];

    // Status checks
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

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
            ($this->document_expiry_date && $this->document_expiry_date->isPast());
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Actions
    public function submit(): void
    {
        $this->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function startReview(): void
    {
        $this->update([
            'status' => 'under_review',
        ]);
    }

    public function approve(string $adminId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $adminId,
            'admin_notes' => $notes,
            'rejection_reason' => null,
        ]);

        // Also approve the vendor
        $this->vendor->approve();
    }

    public function reject(string $adminId, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $adminId,
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeRequiresAction($query)
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }
}
