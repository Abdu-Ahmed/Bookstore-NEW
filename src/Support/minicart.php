<?php
declare(strict_types=1);

/**
 * Lightweight helper to return mini-cart data for the current session user.
 *
 * Returns:
 * [
 *   'items' => array of cart items (as returned by CartService::getCartForUser),
 *   'count' => int total items (sum of quantities),
 *   'total' => float total price
 * ]
 *
 * This file is intentionally simple and tolerant of errors.
 */

if (! function_exists('get_minicart_data')) {
    function get_minicart_data(): array
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $empty = ['items' => [], 'count' => 0, 'total' => 0.0];

        // Check if user is logged in
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            return $empty;
        }

        // Try to get CartService from global container first (your architecture)
        if (!empty($GLOBALS['container']) && is_object($GLOBALS['container'])) {
            $container = $GLOBALS['container'];
            
            try {
                if (method_exists($container, 'get')) {
                    $cartService = $container->get(\App\Services\CartService::class);
                    if ($cartService && method_exists($cartService, 'getCartForUser')) {
                        $items = $cartService->getCartForUser($userId);
                        
                        if (is_array($items)) {
                            $count = 0;
                            $total = 0.0;
                            
                            foreach ($items as $item) {
                                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                                $price = isset($item['book_price']) ? (float)$item['book_price'] : 0.0;
                                $count += $qty;
                                $total += $qty * $price;
                            }
                            
                            return [
                                'items' => $items,
                                'count' => $count,
                                'total' => $total,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Continue to fallback methods
                error_log('Minicart: Failed to get CartService: ' . $e->getMessage());
            }
        }

        // Fallback: Try CartRepository directly
        if (!empty($GLOBALS['container']) && is_object($GLOBALS['container'])) {
            $container = $GLOBALS['container'];
            
            try {
                if (method_exists($container, 'get')) {
                    $cartRepo = $container->get(\App\Repositories\CartRepository::class);
                    $bookRepo = $container->get(\App\Repositories\Interfaces\BookRepositoryInterface::class);
                    
                    if ($cartRepo && $bookRepo && 
                        method_exists($cartRepo, 'getItemsByUser') && 
                        method_exists($bookRepo, 'findById')) {
                        
                        $rows = $cartRepo->getItemsByUser($userId);
                        $items = [];
                        $count = 0;
                        $total = 0.0;
                        
                        foreach ($rows as $row) {
                            $book = $bookRepo->findById((int)$row['book_id']);
                            $qty = (int)$row['quantity'];
                            $price = isset($book['book_price']) ? (float)$book['book_price'] : 0.0;
                            
                            $items[] = [
                                'cart_item_id' => (int)$row['cart_item_id'],
                                'book_id' => (int)$row['book_id'],
                                'quantity' => $qty,
                                'book_title' => $book['book_title'] ?? '',
                                'book_image' => $book['book_image'] ?? '',
                                'book_price' => $price,
                                'line_total' => $qty * $price,
                            ];
                            
                            $count += $qty;
                            $total += $qty * $price;
                        }
                        
                        return [
                            'items' => $items,
                            'count' => $count,
                            'total' => $total,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Continue to next fallback
                error_log('Minicart: Failed to get CartRepository: ' . $e->getMessage());
            }
        }

        // Legacy fallback: session-based cart (for non-logged-in users or fallback)
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $items = $_SESSION['cart'];
            $count = 0;
            $total = 0.0;
            
            foreach ($items as $item) {
                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                $price = isset($item['book_price']) ? (float)$item['book_price'] : 0.0;
                $count += $qty;
                $total += $qty * $price;
            }

            return [
                'items' => $items,
                'count' => $count,
                'total' => $total,
            ];
        }

        // Return empty if nothing found
        return $empty;
    }
}