<?php

declare(strict_types=1);

use App\Core\Router;
use App\Middleware\AuthMiddleware;

/**
 * Web routes definition with middleware support.
 */
return function (Router $router): void {
    // ========== PUBLIC ROUTES (no auth required) ==========
    
    // Home page
    $router->get('/', 'App\Controllers\HomeController@index');
    $router->get('/home', 'App\Controllers\HomeController@index');

    // Auth routes (public)
    $router->get('/register', 'App\Controllers\AuthController@register');
    $router->post('/register', 'App\Controllers\AuthController@register');
    $router->get('/login', 'App\Controllers\AuthController@login');
    $router->post('/login', 'App\Controllers\AuthController@login');

    // Public book browsing
    $router->get('/books', 'App\Controllers\BooksController@index');
    $router->get('/books/page/{page:\d+}', 'App\Controllers\BooksController@index');
    $router->get('/category/{slug}', 'App\Controllers\BooksController@filterByCategory');
    $router->get('/book-detail/{id:\d+}', 'App\Controllers\BookDetailController@show');

    // Webhooks (for external services)
    $router->post('/webhook/stripe', 'App\Controllers\CheckoutController@webhook');

    // ========== PROTECTED ROUTES (require authentication) ==========
    
    // Logout (protected - only logged in users can logout)
    $router->post('/logout', 'App\Controllers\AuthController@logout', [AuthMiddleware::class]);
    $router->get('/logout', 'App\Controllers\AuthController@logout', [AuthMiddleware::class]);

    // Cart operations (require login)
    $router->get('/cart', 'App\Controllers\CartController@index', [AuthMiddleware::class]);
    $router->post('/cart/add/{bookId:\d+}', 'App\Controllers\CartController@add', [AuthMiddleware::class]);
    $router->post('/cart/update/{cartItemId:\d+}', 'App\Controllers\CartController@update', [AuthMiddleware::class]);
    $router->get('/cart/remove/{cartItemId:\d+}', 'App\Controllers\CartController@remove', [AuthMiddleware::class]);

    // Checkout (require login)
    $router->get('/checkout', 'App\Controllers\CheckoutController@index', [AuthMiddleware::class]);
    $router->post('/checkout/create', 'App\Controllers\CheckoutController@create', [AuthMiddleware::class]);
    $router->get('/checkout/success', 'App\Controllers\CheckoutController@success', [AuthMiddleware::class]);
    $router->get('/checkout/cancel', 'App\Controllers\CheckoutController@cancel', [AuthMiddleware::class]);

    // User account/profile routes (if you have them)
    // $router->get('/account', 'App\Controllers\UserController@account', [AuthMiddleware::class]);
    // $router->get('/orders', 'App\Controllers\OrderController@index', [AuthMiddleware::class]);
};