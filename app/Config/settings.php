<?php

declare(strict_types=1);

// Load environment helper (create this file first)
require_once __DIR__ . '/env.php';

/**
 * Application settings configuration.
 */
return [
    'app' => [
        'name' => env('APP_NAME', 'Bookstore'),
        'debug' => env('APP_DEBUG', 'true') === 'true', // Convert string to boolean
        'base_path' => env('BASE_PATH', 'http://localhost'), 
        'base_url' => env('BASE_URL', 'http://localhost'), // Used for JWT issuer
        'timezone' => 'UTC',
    ],
    
    'auth' => [
        'jwt_secret' => env('JWT_SECRET', 'REDACTED_FOR_SECURITY'), 
        'access_ttl' => 900, // 15 minutes
        'refresh_ttl' => 60 * 60 * 24 * 30, // 30 days
    ],
    
    'db' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'bookstore'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'options' => [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET', 'REDACTED_FOR_SECURITY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE', 'REDACTED_FOR_SECURITY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
    
    'views_path' => APP_ROOT . '/app/Views',

];
