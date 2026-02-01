<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use WeakMap;

/**
 * Memory Manager for Swoole Long-Running Processes
 * 
 * Tracks all registered objects to help identify memory leaks.
 * Use this to store objects that need lifecycle management.
 * 
 * Usage:
 *   MemoryManager::set('user.123', $userObject);
 *   $user = MemoryManager::get('user.123');
 *   MemoryManager::clear('user.123');
 *   MemoryManager::dump(); // See all active objects
 */
class MemoryManager
{
    /**
     * Main storage for managed objects
     */
    protected static array $store = [];

    /**
     * Metadata about stored objects (creation time, type, size estimate)
     */
    protected static array $metadata = [];

    /**
     * Track object creation timestamps for leak detection
     */
    protected static array $timestamps = [];

    /**
     * Maximum age in seconds before warning about potential leaks
     */
    protected static int $maxAge = 300; // 5 minutes

    /**
     * Store an object with a key
     */
    public static function set(string $key, mixed $value, ?string $group = null): void
    {
        $fullKey = $group ? "{$group}.{$key}" : $key;
        
        // Clean up existing if present
        if (isset(self::$store[$fullKey])) {
            self::clear($fullKey);
        }

        self::$store[$fullKey] = $value;
        self::$timestamps[$fullKey] = time();
        self::$metadata[$fullKey] = [
            'type' => is_object($value) ? get_class($value) : gettype($value),
            'size' => self::estimateSize($value),
            'created_at' => date('Y-m-d H:i:s'),
            'group' => $group,
        ];
    }

    /**
     * Get an object by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$store[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        return isset(self::$store[$key]);
    }

    /**
     * Clear a specific key and set to null
     */
    public static function clear(string $key): bool
    {
        if (isset(self::$store[$key])) {
            self::$store[$key] = null;
            unset(self::$store[$key]);
            unset(self::$metadata[$key]);
            unset(self::$timestamps[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all keys in a group
     */
    public static function clearGroup(string $group): int
    {
        $count = 0;
        foreach (array_keys(self::$store) as $key) {
            if (str_starts_with($key, "{$group}.")) {
                self::clear($key);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Clear all stored objects
     */
    public static function clearAll(): int
    {
        $count = count(self::$store);
        
        foreach (array_keys(self::$store) as $key) {
            self::$store[$key] = null;
        }
        
        self::$store = [];
        self::$metadata = [];
        self::$timestamps = [];
        
        // Force garbage collection
        gc_collect_cycles();
        
        return $count;
    }

    /**
     * Get all active keys
     */
    public static function keys(?string $group = null): array
    {
        if ($group === null) {
            return array_keys(self::$store);
        }

        return array_filter(
            array_keys(self::$store),
            fn($key) => str_starts_with($key, "{$group}.")
        );
    }

    /**
     * Get count of stored objects
     */
    public static function count(?string $group = null): int
    {
        return count(self::keys($group));
    }

    /**
     * Get all groups
     */
    public static function groups(): array
    {
        $groups = [];
        foreach (self::$metadata as $meta) {
            if ($meta['group'] && !in_array($meta['group'], $groups)) {
                $groups[] = $meta['group'];
            }
        }
        return $groups;
    }

    /**
     * Dump all active objects with metadata (for debugging)
     */
    public static function dump(): array
    {
        $now = time();
        $items = [];

        foreach (self::$store as $key => $value) {
            $age = $now - (self::$timestamps[$key] ?? $now);
            $meta = self::$metadata[$key] ?? [];

            $items[$key] = [
                'type' => $meta['type'] ?? 'unknown',
                'size_bytes' => $meta['size'] ?? 0,
                'size_human' => self::formatBytes($meta['size'] ?? 0),
                'created_at' => $meta['created_at'] ?? 'unknown',
                'age_seconds' => $age,
                'age_human' => self::formatAge($age),
                'group' => $meta['group'] ?? null,
                'potential_leak' => $age > self::$maxAge,
            ];
        }

        return $items;
    }

    /**
     * Get memory statistics
     */
    public static function stats(): array
    {
        $totalSize = array_sum(array_column(self::$metadata, 'size'));
        $now = time();
        $potentialLeaks = 0;
        $oldestAge = 0;

        foreach (self::$timestamps as $timestamp) {
            $age = $now - $timestamp;
            if ($age > self::$maxAge) {
                $potentialLeaks++;
            }
            $oldestAge = max($oldestAge, $age);
        }

        return [
            'total_objects' => count(self::$store),
            'total_size_bytes' => $totalSize,
            'total_size_human' => self::formatBytes($totalSize),
            'groups' => self::groups(),
            'group_counts' => array_map(fn($g) => self::count($g), self::groups()),
            'potential_leaks' => $potentialLeaks,
            'oldest_age_seconds' => $oldestAge,
            'oldest_age_human' => self::formatAge($oldestAge),
            'php_memory_usage' => self::formatBytes(memory_get_usage(true)),
            'php_memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Find potential memory leaks (objects older than maxAge)
     */
    public static function findLeaks(): array
    {
        $now = time();
        $leaks = [];

        foreach (self::$timestamps as $key => $timestamp) {
            $age = $now - $timestamp;
            if ($age > self::$maxAge) {
                $leaks[$key] = [
                    'age_seconds' => $age,
                    'age_human' => self::formatAge($age),
                    'type' => self::$metadata[$key]['type'] ?? 'unknown',
                    'size' => self::formatBytes(self::$metadata[$key]['size'] ?? 0),
                ];
            }
        }

        return $leaks;
    }

    /**
     * Clean up old objects (potential leaks)
     */
    public static function cleanupOld(?int $maxAge = null): int
    {
        $maxAge = $maxAge ?? self::$maxAge;
        $now = time();
        $cleaned = 0;

        foreach (self::$timestamps as $key => $timestamp) {
            if (($now - $timestamp) > $maxAge) {
                Log::warning("MemoryManager: Cleaning up stale object", [
                    'key' => $key,
                    'age' => $now - $timestamp,
                    'type' => self::$metadata[$key]['type'] ?? 'unknown',
                ]);
                self::clear($key);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            gc_collect_cycles();
        }

        return $cleaned;
    }

    /**
     * Set the maximum age before considering an object a potential leak
     */
    public static function setMaxAge(int $seconds): void
    {
        self::$maxAge = $seconds;
    }

    /**
     * Estimate memory size of a value
     */
    protected static function estimateSize(mixed $value): int
    {
        if (is_null($value)) {
            return 0;
        }

        if (is_bool($value)) {
            return 1;
        }

        if (is_int($value) || is_float($value)) {
            return 8;
        }

        if (is_string($value)) {
            return strlen($value);
        }

        if (is_array($value)) {
            $size = 0;
            foreach ($value as $k => $v) {
                $size += self::estimateSize($k) + self::estimateSize($v);
            }
            return $size;
        }

        if (is_object($value)) {
            // Rough estimate for objects
            try {
                return strlen(serialize($value));
            } catch (\Exception $e) {
                return 1024; // Default estimate
            }
        }

        return 0;
    }

    /**
     * Format bytes to human readable
     */
    protected static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Format age to human readable
     */
    protected static function formatAge(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        if ($seconds < 3600) {
            return round($seconds / 60, 1) . "m";
        }
        return round($seconds / 3600, 1) . "h";
    }

    /**
     * Request-scoped storage (auto-cleared after request)
     * Use this for request-specific data
     */
    public static function request(string $key, mixed $value = null): mixed
    {
        $requestId = self::getRequestId();
        $fullKey = "request.{$requestId}.{$key}";

        if ($value !== null) {
            self::set($fullKey, $value, 'request');
            return $value;
        }

        return self::get($fullKey);
    }

    /**
     * Clear all request-scoped storage for current request
     */
    public static function clearRequest(): int
    {
        $requestId = self::getRequestId();
        return self::clearGroup("request.{$requestId}");
    }

    /**
     * Get current request ID
     */
    protected static function getRequestId(): string
    {
        static $requestId = null;
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        return $requestId;
    }
}
