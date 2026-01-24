<?php

namespace App\Services;

class BrandingService
{
    protected ?array $config = null;

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getPrimaryColor(): string
    {
        return $this->config['theme_color'] ?? '#000000';
    }

    /**
     * Vital for Octane: Reset state between requests
     */
    public function flush(): void
    {
        $this->config = null;
    }
}
