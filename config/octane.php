<?php

use Laravel\Octane\Octane;

return [

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | Swoole tables allow you to share data across all Swoole workers.
    | They are stored in memory and are extremely fast.
    |
    */
    'tables' => [
        'tenants:1000' => [
                'id' => 'int:4',
                'theme_color' => 'string:7',
        ],
    ],

    'swoole' => [
        'options' => [
            'max_request' => 1000,
            'worker_num' => 8, // Or swoole_cpu_num() * 2
            'task_worker_num' => 4,
        ],
    ],

    'flush' => [
        \App\Services\BrandingService::class,
    ],
];
