<?php

namespace App\Services;

use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Resolves the current tenant (marketplace) from the request and sets the DB connection.
 * Use in middleware so all Eloquent queries use the tenant's database.
 */
class TenantConnectionResolver
{
    protected ?Marketplace $currentMarketplace = null;

    public function resolveFromRequest(Request $request): ?Marketplace
    {
        $slug = $this->getTenantSlugFromRequest($request);
        if (! $slug) {
            return null;
        }

        $this->currentMarketplace = Marketplace::on('platform')
            ->where('slug', $slug)
            ->where('status', 'approved')
            ->first();

        if ($this->currentMarketplace && $this->currentMarketplace->hasTenantDatabase()) {
            $this->registerTenantConnectionIfNeeded($this->currentMarketplace);
            $this->setDefaultConnection($this->currentMarketplace->db_connection_name);
        }

        return $this->currentMarketplace;
    }

    protected function registerTenantConnectionIfNeeded(Marketplace $marketplace): void
    {
        $name = $marketplace->db_connection_name;
        if (Config::get("database.connections.{$name}")) {
            return;
        }
        $driver = config('database.connections.platform.driver', config('database.default'));
        $config = array_merge(
            config("database.connections.{$driver}") ?? config('database.connections.' . config('database.default')),
            [
                'database' => $marketplace->db_database,
                'host' => $marketplace->db_host,
                'port' => $marketplace->db_port,
                'username' => $marketplace->db_username,
                'password' => $marketplace->db_password_encrypted ? Crypt::decryptString($marketplace->db_password_encrypted) : '',
            ]
        );
        Config::set("database.connections.{$name}", $config);
    }

    public function getCurrentMarketplace(): ?Marketplace
    {
        return $this->currentMarketplace;
    }

    public function setDefaultConnection(string $connectionName): void
    {
        Config::set('database.default', $connectionName);
        app()->instance('db', app('db')->connection($connectionName));
    }

    protected function getTenantSlugFromRequest(Request $request): ?string
    {
        // Subdomain: acme.pcommerce.com → acme
        $host = $request->getHost();
        $domain = config('app.domain', 'pcommerce.com');
        if (Str::endsWith($host, $domain) && $host !== $domain) {
            $subdomain = Str::before($host, '.' . $domain);

            return $subdomain !== 'www' ? $subdomain : null;
        }

        // Header (for API or same-domain routing)
        if ($request->header('X-Tenant')) {
            return $request->header('X-Tenant');
        }

        return null;
    }
}
