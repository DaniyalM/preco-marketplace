<?php

use App\Support\MemoryManager;

if (!function_exists('memory')) {
    /**
     * Access the MemoryManager
     * 
     * Usage:
     *   memory()->set('key', $value);
     *   memory()->get('key');
     *   memory('key', $value);  // Set
     *   memory('key');          // Get
     */
    function memory(?string $key = null, mixed $value = null): mixed
    {
        if ($key === null) {
            return new class {
                public function set(string $key, mixed $value, ?string $group = null): void
                {
                    MemoryManager::set($key, $value, $group);
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return MemoryManager::get($key, $default);
                }

                public function has(string $key): bool
                {
                    return MemoryManager::has($key);
                }

                public function clear(string $key): bool
                {
                    return MemoryManager::clear($key);
                }

                public function clearGroup(string $group): int
                {
                    return MemoryManager::clearGroup($group);
                }

                public function clearAll(): int
                {
                    return MemoryManager::clearAll();
                }

                public function dump(): array
                {
                    return MemoryManager::dump();
                }

                public function stats(): array
                {
                    return MemoryManager::stats();
                }

                public function request(string $key, mixed $value = null): mixed
                {
                    return MemoryManager::request($key, $value);
                }
            };
        }

        if ($value !== null) {
            MemoryManager::set($key, $value);
            return $value;
        }

        return MemoryManager::get($key);
    }
}

if (!function_exists('mem_request')) {
    /**
     * Store/retrieve request-scoped data (auto-cleared after request)
     * 
     * Usage:
     *   mem_request('user', $user);  // Store
     *   $user = mem_request('user'); // Retrieve
     */
    function mem_request(string $key, mixed $value = null): mixed
    {
        return MemoryManager::request($key, $value);
    }
}
