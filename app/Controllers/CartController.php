<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Models\CartItem;

/**
 * CartController with proper authentication handling and model objects
 */
final class CartController extends Controller
{
    private $cartModel = null;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        // Try to resolve cart service/repository
        try {
            $this->cartModel = $this->container->get(\App\Repositories\CartRepository::class);
        } catch (\Throwable $e1) {
            try {
                $this->cartModel = $this->container->get(\App\Services\CartService::class);
            } catch (\Throwable $e2) {
                $this->cartModel = null;
            }
        }
    }

/**
 * POST /cart/add/{bookId}
 */
public function add(Request $request, int $bookId): Response
{
    // Use the base controller's auth check
    if ($authRedirect = $this->requireAuth()) {
        return $authRedirect;
    }

    $userId = $this->getCurrentUserId();
    $quantity = (int) $this->getParam('quantity', 1);
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Session fallback if no persistent cart model
    if ($this->cartModel === null) {
        $this->handleSessionCart($bookId, $quantity);
    } else {
        // Use persistent cart model
        try {
            if (method_exists($this->cartModel, 'addItem')) {
                $this->cartModel->addItem($userId, $bookId, $quantity);
            } else {
                $this->handleSessionCart($bookId, $quantity);
            }
        } catch (\Throwable $e) {
            $this->handleSessionCart($bookId, $quantity);
        }
    }

    $baseUrl = $this->container->get('settings')['app']['base_path'] ?? '';
    return $this->redirect($baseUrl . '/cart');
}

    /**
     * GET /cart
     */
    public function index(): Response
    {
        // Check authentication but don't require it (allow guest cart viewing)
        $userId = $this->getCurrentUserId();
        $items = [];

        if ($this->cartModel !== null && $userId) {
            try {
                if (method_exists($this->cartModel, 'getItemsByUser')) {
                    /** @var CartItem[] $cartItems */
                    $cartItems = $this->cartModel->getItemsByUser($userId);
                    $items = $this->convertCartItemsToArray($cartItems);
                }
            } catch (\Throwable $e) {
                error_log("CartController::index - error fetching persistent items: " . $e->getMessage());
                $items = [];
            }
        }

        // Always fallback to session cart if no items found
        if (empty($items)) {
            $this->ensureSession();
            $items = $_SESSION['cart'] ?? [];
        }

        $minicart = $this->buildMiniCart($items);

        return $this->view('cart/view', [
            'items' => $items,
            'minicart' => $minicart,
        ]);
    }

    /**
     * POST /cart/update/{cartItemId}
     */
    public function update(Request $request, string $cartItemId): Response
    {
        if ($authRedirect = $this->requireAuth()) {
            return $authRedirect;
        }

        $quantity = (int) $this->getParam('quantity', 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        if ($this->cartModel !== null) {
            try {
                if (method_exists($this->cartModel, 'updateQuantity')) {
                    $this->cartModel->updateQuantity((int)$cartItemId, $quantity);
                } else {
                    $this->updateSessionCart($cartItemId, $quantity);
                }
            } catch (\Throwable $e) {
                $this->updateSessionCart($cartItemId, $quantity);
            }
        } else {
            $this->updateSessionCart($cartItemId, $quantity);
        }

        $baseUrl = $this->container->get('settings')['app']['base_path'] ?? '';
        return $this->redirect($baseUrl . '/cart');
    }

    /**
     * GET|POST /cart/remove/{cartItemId}
     */
    public function remove(Request $request, string $cartItemId): Response
    {
        if ($authRedirect = $this->requireAuth()) {
            return $authRedirect;
        }

        if ($this->cartModel !== null) {
            try {
                if (method_exists($this->cartModel, 'removeItem')) {
                    $this->cartModel->removeItem((int)$cartItemId);
                } else {
                    $this->removeFromSessionCart($cartItemId);
                }
            } catch (\Throwable $e) {
                $this->removeFromSessionCart($cartItemId);
            }
        } else {
            $this->removeFromSessionCart($cartItemId);
        }

        $baseUrl = $this->container->get('settings')['app']['base_path'] ?? '';
        return $this->redirect($baseUrl . '/cart');
    }

    /**
     * Convert CartItem objects to array format for view compatibility
     * @param CartItem[] $cartItems
     * @return array<int,array<string,mixed>>
     */
    private function convertCartItemsToArray(array $cartItems): array
    {
        $items = [];
        foreach ($cartItems as $cartItem) {
            $items[] = [
                'cart_item_id' => $cartItem->cart_item_id,
                'book_id' => $cartItem->book_id,
                'book_title' => $cartItem->book_title,
                'book_image' => $cartItem->book_image,
                'book_price' => $cartItem->book_price,
                'quantity' => $cartItem->quantity,
                'subtotal' => $cartItem->subtotal(),
            ];
        }
        return $items;
    }

    /**
     * Handle session cart operations
     */
    private function handleSessionCart(int $bookId, int $quantity): void
    {
        $this->ensureSession();
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];

        // Check if item exists and increment
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if (isset($item['book_id']) && (int)$item['book_id'] === $bookId) {
                $item['quantity'] = ((int)$item['quantity']) + $quantity;
                $found = true;
                error_log("Session cart - incremented existing item book_id={$bookId}");
                break;
            }
        }
        unset($item);

        if (!$found) {
            // Load book details and add new item
            $bookInfo = $this->loadBookData($bookId);
            $newItem = [
                'cart_item_id' => uniqid('ci_', true),
                'book_id' => $bookId,
                'book_title' => $bookInfo['book_title'] ?? '',
                'book_image' => $bookInfo['book_image'] ?? '',
                'book_price' => $bookInfo['book_price'] ?? 0.0,
                'quantity' => $quantity,
            ];
            $_SESSION['cart'][] = $newItem;
            error_log("Session cart - added new item: " . print_r($newItem, true));
        }
    }

    /**
     * Update session cart item quantity
     */
    private function updateSessionCart(string $cartItemId, int $quantity): void
    {
        $this->ensureSession();
        foreach ($_SESSION['cart'] as &$item) {
            if (($item['cart_item_id'] ?? '') === $cartItemId) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        unset($item);
    }

    /**
     * Remove item from session cart
     */
    private function removeFromSessionCart(string $cartItemId): void
    {
        $this->ensureSession();
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], function ($item) use ($cartItemId) {
            return ($item['cart_item_id'] ?? '') !== $cartItemId;
        }));
    }

    /**
     * Build mini cart summary
     */
    private function buildMiniCart(array $items): array
    {
        $count = 0;
        $subtotal = 0.0;
        $preview = [];

        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['book_price'] ?? 0.0);
            $count += $qty;
            $subtotal += $qty * $price;

            if (count($preview) < 4) {
                $preview[] = [
                    'book_id' => $item['book_id'] ?? null,
                    'title' => $item['book_title'] ?? '',
                    'image' => $item['book_image'] ?? '',
                    'price' => $price,
                    'quantity' => $qty,
                ];
            }
        }

        return [
            'count' => $count,
            'subtotal' => $subtotal,
            'items' => $preview,
        ];
    }

    /**
     * Helper to get request parameters
     */
    private function getParam(string $name, $default = null)
    {
        try {
            if ($this->request instanceof Request) {
                if (method_exists($this->request, 'getParsedBody')) {
                    $body = $this->request->getParsedBody();
                    if (is_array($body) && array_key_exists($name, $body)) {
                        return $body[$name];
                    }
                }
                if (method_exists($this->request, 'getQueryParams')) {
                    $q = $this->request->getQueryParams();
                    if (is_array($q) && array_key_exists($name, $q)) {
                        return $q[$name];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback to superglobals
        }

        return $_POST[$name] ?? $_GET[$name] ?? $_REQUEST[$name] ?? $default;
    }

    /**
     * Ensure session is started
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Load book data for session cart
     */
    private function loadBookData(int $bookId): array
    {
        try {
            // Try BookService first
            try {
                $service = $this->container->get(\App\Services\BookService::class);
                if (method_exists($service, 'getById')) {
                    $book = $service->getById($bookId);
                    return is_array($book) ? $book : (array)$book;
                }
            } catch (\Throwable $e) {
                // Continue to next attempt
            }

            // Try BookRepository
            try {
                $repo = $this->container->get(\App\Repositories\BookRepository::class);
                if (method_exists($repo, 'findById')) {
                    $book = $repo->findById($bookId);
                    return is_array($book) ? $book : (array)$book;
                }
            } catch (\Throwable $e) {
                // Continue to next attempt
            }

            // Try legacy model
            if (class_exists(\App\Models\Book::class)) {
                $model = new \App\Models\Book();
                if (method_exists($model, 'getBookById')) {
                    return (array)$model->getBookById($bookId);
                }
            }
        } catch (\Throwable $e) {
            // Return empty array on all failures
        }

        return [];
    }
}