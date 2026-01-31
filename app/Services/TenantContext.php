<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Tenant context service for multi-tenant operations.
 * 
 * Provides access to the current tenant ID and user ID
 * extracted from the JWT token. No database or session lookups.
 */
class TenantContext
{
    protected ?string $tenantId = null;
    protected ?string $userId = null;
    protected bool $resolved = false;

    /**
     * Resolve tenant and user from the current request
     */
    public function resolve(?Request $request = null): void
    {
        if ($this->resolved) {
            return;
        }

        $request = $request ?? request();
        
        $this->tenantId = $request->attributes->get('tenant_id');
        $this->userId = $request->attributes->get('user_id');
        $this->resolved = true;
    }

    /**
     * Get current tenant ID
     */
    public function getTenantId(): ?string
    {
        $this->resolve();
        return $this->tenantId;
    }

    /**
     * Get current user ID
     */
    public function getUserId(): ?string
    {
        $this->resolve();
        return $this->userId;
    }

    /**
     * Set tenant ID manually (useful for jobs, commands)
     */
    public function setTenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;
        $this->resolved = true;
        return $this;
    }

    /**
     * Set user ID manually (useful for jobs, commands)
     */
    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        $this->resolved = true;
        return $this;
    }

    /**
     * Check if running in tenant context
     */
    public function hasTenant(): bool
    {
        return $this->getTenantId() !== null;
    }

    /**
     * Check if user is authenticated
     */
    public function hasUser(): bool
    {
        return $this->getUserId() !== null;
    }

    /**
     * Reset context (useful for testing)
     */
    public function reset(): void
    {
        $this->tenantId = null;
        $this->userId = null;
        $this->resolved = false;
    }
}
