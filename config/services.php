<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Keycloak Configuration (Stateless JWT Auth)
    |--------------------------------------------------------------------------
    |
    | Configure your Keycloak instance for stateless authentication.
    | The realm file is at: realms/pcommerce.json
    |
    */
    'keycloak' => [
        'base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080'),
        'realm' => env('KEYCLOAK_REALM', 'pcommerce'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'pcommerce-app'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        
        // Admin CLI client for service-to-service calls (optional)
        'admin_client_id' => env('KEYCLOAK_ADMIN_CLIENT_ID', 'pcommerce-admin-cli'),
        'admin_client_secret' => env('KEYCLOAK_ADMIN_CLIENT_SECRET'),
    ],

];
