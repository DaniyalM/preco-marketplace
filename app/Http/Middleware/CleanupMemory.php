<?php

namespace App\Http\Middleware;

use App\Support\MemoryManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to clean up request-scoped memory after each request.
 * 
 * Essential for Swoole to prevent memory leaks between requests.
 */
class CleanupMemory
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Clean up request-scoped storage
        MemoryManager::clearRequest();

        // Periodically cleanup old objects (every ~100 requests)
        if (mt_rand(1, 100) === 1) {
            MemoryManager::cleanupOld();
        }

        return $response;
    }

    /**
     * Perform cleanup after response is sent (terminable middleware)
     */
    public function terminate(Request $request, Response $response): void
    {
        // Additional cleanup after response
        MemoryManager::clearRequest();
        
        // Log memory stats occasionally for monitoring
        if (mt_rand(1, 1000) === 1 && config('app.debug')) {
            $stats = MemoryManager::stats();
            if ($stats['potential_leaks'] > 0) {
                \Log::warning('MemoryManager: Potential memory leaks detected', [
                    'leaks' => $stats['potential_leaks'],
                    'total_objects' => $stats['total_objects'],
                    'memory' => $stats['php_memory_usage'],
                ]);
            }
        }
    }
}
