<?php

declare(strict_types=1);

/**
 * Service registration for the DI container.
 * This file should be saved as: app/Config/services.php
 */
return function (App\Core\Container $container): void {
    $settings = $container->get('settings');

    // PDO Database Connection
    $container->set(\PDO::class, function ($c) use ($settings) {
        $db = $settings['db'] ?? [];
        $driver  = $db['driver']  ?? 'mysql';
        $host    = $db['host']    ?? '127.0.0.1';
        $port    = $db['port']    ?? '3306';
        $name    = $db['database'] ?? '';
        $charset = $db['charset'] ?? 'utf8mb4';
        $user    = $db['username'] ?? '';
        $pass    = $db['password'] ?? '';
        $options = $db['options']  ?? [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $driver, $host, $port, $name, $charset);
        return new \PDO($dsn, $user, $pass, $options);
    });

    // Logger service (requires Monolog via Composer)
    $container->set('logger', function () {
        // Make sure the logs directory exists
        $logDir = APP_ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logger = new \Monolog\Logger('app');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logDir . '/app.log'));
        return $logger;
    });

    // Book Repository - register both interface and concrete class
    $container->set(App\Repositories\BookRepository::class, function ($c) {
        /** @var \PDO $pdo */
        $pdo = $c->get(\PDO::class);
        return new App\Repositories\BookRepository($pdo);
    });
    
    $container->set(App\Repositories\Interfaces\BookRepositoryInterface::class, function ($c) {
        return $c->get(App\Repositories\BookRepository::class);
    });

    // User Repository
    $container->set(App\Repositories\UserRepository::class, function ($c) {
        $pdo = $c->get(\PDO::class);
        return new App\Repositories\UserRepository($pdo);
    });

    // Refresh Token Repository
    $container->set(App\Repositories\RefreshTokenRepository::class, function ($c) {
        $pdo = $c->get(\PDO::class);
        return new App\Repositories\RefreshTokenRepository($pdo);
    });

    // Auth Services
    $container->set(App\Services\ValidatorService::class, function ($c) {
        return new App\Services\ValidatorService();
    });

$container->set(App\Services\OrderService::class, function ($c) {
    $repo = $c->get(App\Repositories\OrderRepository::class);
    // BookRepositoryInterface is optional helper for mapping titles -> ids (not required)
    $bookRepo = $c->has(App\Repositories\Interfaces\BookRepositoryInterface::class) ? $c->get(App\Repositories\Interfaces\BookRepositoryInterface::class) : null;
    $settings = $c->get('settings')['stripe'] ?? [];
    return new App\Services\OrderService($repo, $bookRepo, $settings);
});

$container->set(App\Services\AuthService::class, function($c) {
    $userRepo = $c->get(App\Repositories\UserRepository::class);
    $refreshTokenRepo = $c->get(App\Repositories\RefreshTokenRepository::class);
    $settings = $c->get('settings');
    return new App\Services\AuthService($userRepo, $refreshTokenRepo, $settings);
});

        // BookService
    $container->set(App\Services\BookService::class, function ($c) {
        $repo = $c->get(App\Repositories\Interfaces\BookRepositoryInterface::class);
        return new App\Services\BookService($repo);
    });
    // Cart repository + service
$container->set(App\Repositories\CartRepository::class, function ($c) {
    $pdo = $c->get(\PDO::class);
    return new App\Repositories\CartRepository($pdo);
});

$container->set(App\Services\CartService::class, function ($c) {
    $cartRepo = $c->get(App\Repositories\CartRepository::class);
    $bookRepo = $c->get(App\Repositories\Interfaces\BookRepositoryInterface::class);
    return new App\Services\CartService($cartRepo, $bookRepo);
});

// Controller factory
$container->set(App\Controllers\CartController::class, function ($c) {
    // Return controller constructed with the container
    return new App\Controllers\CartController($c);
});

// register StripeClient
$container->set(\Stripe\StripeClient::class, function ($c) use ($settings) {
    $stripeSettings = $c->get('settings')['stripe'] ?? [];
    $secret = $stripeSettings['secret_key'] ?? '';
    if ($secret === '') {
        // safe default — it'll fail clearly if not set
        throw new \RuntimeException('Stripe secret key not configured in settings.');
    }
    return new \Stripe\StripeClient($secret);
});

// CheckoutService
$container->set(App\Services\CheckoutService::class, function ($c) {
    $stripe = $c->get(\Stripe\StripeClient::class);
    $currency = $c->get('settings')['stripe']['currency'] ?? 'usd';
    return new App\Services\CheckoutService($stripe, $currency);
});

$container->set(App\Repositories\OrderRepository::class, function ($c) {
    $pdo = $c->get(\PDO::class);
    return new App\Repositories\OrderRepository($pdo);
});


};