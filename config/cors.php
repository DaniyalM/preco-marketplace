<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Define which paths should have CORS headers applied. Wildcards (*) are
    | supported. This typically includes your API routes.
    |
    */

    'paths' => [
        'api/*',
        'auth/*',
        'sanctum/csrf-cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | These are the HTTP methods that are allowed for cross-origin requests.
    | Use ['*'] to allow all methods.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | These are the origins that are allowed to make cross-origin requests.
    | Use ['*'] to allow all origins. For production, specify exact domains.
    |
    */

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost'),
        env('APP_URL', 'http://localhost'),
        'http://localhost:*',
        'https://localhost:*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns that match allowed origins using regex.
    |
    */

    'allowed_origins_patterns' => [
        // Allow any subdomain of your main domain
        // '#^https?://.*\.yourdomain\.com$#',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | These are the headers that are allowed in cross-origin requests.
    | Use ['*'] to allow all headers.
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | These headers are exposed to the browser JavaScript in cross-origin
    | requests. Add any custom headers your frontend needs access to.
    |
    */

    'exposed_headers' => [
        'X-Request-Id',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preflight Max Age (OPTIONS Caching)
    |--------------------------------------------------------------------------
    |
    | The max age (in seconds) that the browser should cache the CORS
    | preflight response. This prevents browsers from sending OPTIONS
    | requests repeatedly for the same endpoint.
    |
    | Recommended values:
    | - 86400 = 24 hours (safe default)
    | - 604800 = 7 days (aggressive caching)
    | - 7200 = 2 hours (Chrome's internal max)
    |
    | Note: Some browsers have internal limits:
    | - Chrome: 2 hours (7200 seconds) max
    | - Firefox: 24 hours (86400 seconds) max
    | - Safari: variable
    |
    | We set 86400 (24 hours) as it works well across browsers while
    | respecting their internal limits.
    |
    */

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | When set to true, cookies and authorization headers are included
    | in cross-origin requests. Required for JWT in HTTP-only cookies.
    |
    */

    'supports_credentials' => true,

];
