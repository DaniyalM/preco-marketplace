<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MemoryManager;
use Illuminate\Http\Request;

/**
 * Memory inspection API for debugging Swoole memory leaks
 * 
 * WARNING: Only enable in development! Disable in production.
 */
class MemoryController extends Controller
{
    /**
     * Get memory statistics and active objects
     */
    public function index()
    {
        return response()->json([
            'stats' => MemoryManager::stats(),
            'objects' => MemoryManager::dump(),
            'potential_leaks' => MemoryManager::findLeaks(),
        ]);
    }

    /**
     * Get only statistics
     */
    public function stats()
    {
        return response()->json(MemoryManager::stats());
    }

    /**
     * Get all active keys
     */
    public function keys(Request $request)
    {
        $group = $request->query('group');
        
        return response()->json([
            'keys' => MemoryManager::keys($group),
            'count' => MemoryManager::count($group),
        ]);
    }

    /**
     * Get a specific object's metadata
     */
    public function show(string $key)
    {
        if (!MemoryManager::has($key)) {
            return response()->json([
                'error' => 'Key not found',
                'key' => $key,
            ], 404);
        }

        $dump = MemoryManager::dump();
        
        return response()->json([
            'key' => $key,
            'metadata' => $dump[$key] ?? null,
            'exists' => true,
        ]);
    }

    /**
     * Clear a specific key
     */
    public function destroy(string $key)
    {
        $existed = MemoryManager::has($key);
        $cleared = MemoryManager::clear($key);

        return response()->json([
            'key' => $key,
            'cleared' => $cleared,
            'existed' => $existed,
        ]);
    }

    /**
     * Clear a group of keys
     */
    public function destroyGroup(string $group)
    {
        $count = MemoryManager::clearGroup($group);

        return response()->json([
            'group' => $group,
            'cleared_count' => $count,
        ]);
    }

    /**
     * Clear all keys
     */
    public function destroyAll()
    {
        $count = MemoryManager::clearAll();

        return response()->json([
            'cleared_count' => $count,
            'gc_collected' => gc_collect_cycles(),
        ]);
    }

    /**
     * Find and list potential memory leaks
     */
    public function leaks()
    {
        return response()->json([
            'leaks' => MemoryManager::findLeaks(),
            'count' => count(MemoryManager::findLeaks()),
            'threshold_seconds' => 300, // 5 minutes
        ]);
    }

    /**
     * Clean up old/stale objects
     */
    public function cleanup(Request $request)
    {
        $maxAge = $request->query('max_age', 300);
        $cleaned = MemoryManager::cleanupOld($maxAge);

        return response()->json([
            'cleaned_count' => $cleaned,
            'max_age_seconds' => $maxAge,
            'gc_collected' => gc_collect_cycles(),
            'memory_after' => MemoryManager::stats()['php_memory_usage'],
        ]);
    }

    /**
     * Force garbage collection
     */
    public function gc()
    {
        $before = memory_get_usage(true);
        $cycles = gc_collect_cycles();
        $after = memory_get_usage(true);

        return response()->json([
            'cycles_collected' => $cycles,
            'memory_before' => $this->formatBytes($before),
            'memory_after' => $this->formatBytes($after),
            'memory_freed' => $this->formatBytes($before - $after),
        ]);
    }

    /**
     * Set a test value (for debugging)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'group' => 'nullable|string',
        ]);

        MemoryManager::set(
            $validated['key'],
            $validated['value'],
            $validated['group'] ?? null
        );

        return response()->json([
            'key' => $validated['key'],
            'stored' => true,
        ]);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
