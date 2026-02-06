<?php

namespace App\Services;

use App\Models\Marketplace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Provisions a separate database for an approved marketplace (tenant).
 * Ensures data segregation and isolation per tenant.
 */
class MarketplaceProvisioningService
{

    /**
     * Create the tenant database and run migrations.
     * Call this after KYC is approved.
     */
    public function provisionTenantDatabase(Marketplace $marketplace): void
    {
        $driver = config('database.default');
        $database = $this->tenantDatabaseName($marketplace->slug);

        if ($driver === 'pgsql') {
            $this->createPostgresDatabase($database);
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->createMySqlDatabase($database);
        } else {
            throw new \RuntimeException('Tenant provisioning only supports MySQL and PostgreSQL.');
        }

        $connectionName = $this->registerTenantConnection($marketplace, $database);
        $this->runTenantMigrations($connectionName);

        $marketplace->update([
            'db_connection_name' => $connectionName,
            'db_database' => $database,
            'db_host' => config('database.connections.' . config('database.default') . '.host'),
            'db_port' => config('database.connections.' . config('database.default') . '.port'),
            'db_username' => config('database.connections.' . config('database.default') . '.username'),
            'db_password_encrypted' => Crypt::encryptString(config('database.connections.' . config('database.default') . '.password')),
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    protected function tenantDatabaseName(string $slug): string
    {
        $prefix = config('database.tenant_prefix', 'pcommerce_tenant_');

        return $prefix . Str::slug($slug, '_');
    }

    protected function createPostgresDatabase(string $database): void
    {
        $config = config('database.connections.pgsql');
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'];

        // Connect to postgres (template) to create the new database
        $dsn = "pgsql:host={$host};port={$port};dbname=postgres";
        $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $safeName = preg_replace('/[^a-z0-9_]/', '', $database);
        $pdo->exec("CREATE DATABASE \"{$safeName}\" ENCODING 'UTF8'");
    }

    protected function createMySqlDatabase(string $database): void
    {
        $config = config('database.connections.mysql');
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'];

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Register a dynamic connection for the tenant and return its name.
     */
    protected function registerTenantConnection(Marketplace $marketplace, string $database): string
    {
        $connectionName = 'tenant_' . Str::slug($marketplace->slug, '_');
        $defaultConfig = config('database.connections.' . config('database.default'));

        $config = array_merge($defaultConfig, [
            'database' => $database,
        ]);

        Config::set("database.connections.{$connectionName}", $config);

        return $connectionName;
    }

    /**
     * Run migrations on the tenant connection (creates vendors, products, orders, etc.).
     */
    protected function runTenantMigrations(string $connectionName): void
    {
        $exitCode = Artisan::call('migrate', [
            '--database' => $connectionName,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                'Tenant migrations failed: ' . Artisan::output()
            );
        }
    }
}
