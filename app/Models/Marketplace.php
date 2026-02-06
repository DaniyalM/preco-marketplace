<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marketplace extends Model
{
    use SoftDeletes;

    protected $connection = 'platform';

    protected $table = 'marketplaces';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'support_email',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'db_connection_name',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password_encrypted',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected $hidden = [
        'db_password_encrypted',
    ];

    public function kyc(): HasOne
    {
        return $this->hasOne(MarketplaceKyc::class);
    }

    public function isPendingKyc(): bool
    {
        return $this->status === 'pending_kyc';
    }

    public function isKycUnderReview(): bool
    {
        return $this->status === 'kyc_under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasTenantDatabase(): bool
    {
        return ! empty($this->db_connection_name) && ! empty($this->db_database);
    }
}
