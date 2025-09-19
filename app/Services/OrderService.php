<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\OrderRepository;
use App\Repositories\Interfaces\BookRepositoryInterface;

final class OrderService
{
    private OrderRepository $orders;
    private ?BookRepositoryInterface $books;
    private array $settings;

    public function __construct(OrderRepository $orders, ?BookRepositoryInterface $books = null, array $settings = [])
    {
        $this->orders = $orders;
        $this->books = $books;
        $this->settings = $settings;
    }

    /**
     * Given a Stripe session object and its line_items (array), persist the order.
     * Ensures idempotency - if session already recorded, returns existing order.
     *
     * @param string $sessionId
     * @param array $sessionData   // raw session object array (must contain amount_total, currency, client_reference_id)
     * @param array $lineItems     // array of expanded line items: each item should contain price_data->unit_amount and quantity and product_data->name
     * @return Order
     */
    public function createFromStripeSession(string $sessionId, array $sessionData, array $lineItems): Order
    {
        // idempotency: check existing
        $existing = $this->orders->findBySessionId($sessionId);
        if ($existing !== null) {
            return $existing;
        }

        $userId = null;
        if (!empty($sessionData['client_reference_id'])) {
            $userId = (int)$sessionData['client_reference_id'];
        }

        // build items array for DB: book_id (if we can map by product name or metadata), title, unit_price (cents), quantity
        $items = [];
        foreach ($lineItems as $li) {
            // If Stripe line_items provided as objects convert safely
            $priceData = $li['price_data'] ?? ($li['price'] ?? null);
            $productData = $li['price_data']['product_data'] ?? ($li['description'] ?? null);

            $unitAmount = (int)(($priceData['unit_amount'] ?? $li['price']['unit_amount'] ?? 0));
            $quantity = (int)($li['quantity'] ?? 1);
            $title = $productData['name'] ?? ($li['description'] ?? 'Item');
            
            // Try to match book_id if we have book repository
            $bookId = $this->findBookIdByTitle($title);
            
            $items[] = [
                'book_id' => $bookId,
                'title' => $title,
                'unit_price' => $unitAmount,
                'quantity' => $quantity,
            ];
        }

        $amount = (int) ($sessionData['amount_total'] ?? ($sessionData['amount_subtotal'] ?? 0));
        $currency = $sessionData['currency'] ?? 'usd';

        return $this->orders->createOrder($userId, $sessionId, $amount, $currency, $items, $sessionData['metadata'] ?? null);
    }

    /**
     * Find order by session ID
     */
    public function findBySessionId(string $sessionId): ?Order
    {
        return $this->orders->findBySessionId($sessionId);
    }

    /**
     * Find order by ID
     */
    public function findById(int $orderId): ?Order
    {
        return $this->orders->findById($orderId);
    }

    /**
     * Get order summary with calculated totals
     */
    public function getOrderSummary(Order $order): array
    {
        $itemCount = count($order->items);
        $totalQuantity = $order->totalQuantity();
        $subtotal = 0;

        foreach ($order->items as $item) {
            $subtotal += $item->subtotal();
        }

        return [
            'order' => $order,
            'item_count' => $itemCount,
            'total_quantity' => $totalQuantity,
            'subtotal_cents' => $subtotal,
            'subtotal_dollars' => $subtotal / 100,
            'amount_cents' => $order->amount,
            'amount_dollars' => $order->amount / 100,
        ];
    }

    /**
     * Get orders for a user (if we add this functionality later)
     */
    public function getUserOrders(int $userId): array
    {
        // This would require adding getUserOrders method to OrderRepository
        // For now, return empty array or throw not implemented
        throw new \BadMethodCallException('getUserOrders not yet implemented');
    }

    /**
     * Validate order belongs to user
     */
    public function validateOrderOwnership(int $orderId, int $userId): bool
    {
        $order = $this->orders->findById($orderId);
        return $order !== null && $order->user_id === $userId;
    }

    /**
     * Try to find book ID by title (basic implementation)
     */
    private function findBookIdByTitle(string $title): ?int
    {
        if ($this->books === null) {
            return null;
        }

        try {
            // This assumes BookRepository has a findByTitle method
            // You might need to add this method or use a different approach
            if (method_exists($this->books, 'findByTitle')) {
                $book = $this->books->findByTitle($title);
                return $book ? $book['book_id'] : null;
            }
        } catch (\Throwable $e) {
            // If we can't find the book, that's okay - we'll store null
            error_log("Could not find book by title '{$title}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Format order for display/API
     */
    public function formatOrderForDisplay(Order $order): array
    {
        $formattedItems = [];
        foreach ($order->items as $item) {
            $formattedItems[] = [
                'order_item_id' => $item->order_item_id,
                'book_id' => $item->book_id,
                'title' => $item->title,
                'unit_price_cents' => $item->unit_price,
                'unit_price_dollars' => $item->unit_price / 100,
                'quantity' => $item->quantity,
                'subtotal_cents' => $item->subtotal(),
                'subtotal_dollars' => $item->subtotal() / 100,
            ];
        }

        return [
            'order_id' => $order->order_id,
            'user_id' => $order->user_id,
            'session_id' => $order->session_id,
            'amount_cents' => $order->amount,
            'amount_dollars' => $order->amount / 100,
            'currency' => $order->currency,
            'status' => $order->status,
            'metadata' => $order->metadata,
            'items' => $formattedItems,
            'total_quantity' => $order->totalQuantity(),
        ];
    }
}