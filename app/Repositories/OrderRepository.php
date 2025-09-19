<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use PDO;

final class OrderRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Find order by Stripe session_id */
    public function findBySessionId(string $sessionId): ?Order
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE session_id = :s LIMIT 1');
        $stmt->execute([':s' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $order = new Order($row);
        $order->items = $this->getOrderItems($order->order_id);
        
        return $order;
    }

    /** Find order by ID with items */
    public function findById(int $orderId): ?Order
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE order_id = :id LIMIT 1');
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $order = new Order($row);
        $order->items = $this->getOrderItems($orderId);
        
        return $order;
    }

    /** Get all order items for an order */
    private function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $items = [];
        foreach ($rows as $row) {
            $items[] = new OrderItem($row);
        }
        
        return $items;
    }

    /** 
     * Create order with items in a transaction. $amount is cents. 
     * @param array<int,array<string,mixed>> $items
     */
    public function createOrder(?int $userId, string $sessionId, int $amount, string $currency, array $items, ?array $metadata = null): Order
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO orders (user_id, session_id, amount, currency, metadata, status) VALUES (:user, :session, :amount, :currency, :metadata, :status)');
            $stmt->execute([
                ':user' => $userId,
                ':session' => $sessionId,
                ':amount' => $amount,
                ':currency' => $currency,
                ':metadata' => $metadata ? json_encode($metadata) : null,
                ':status' => 'paid',
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare('INSERT INTO order_items (order_id, book_id, title, unit_price, quantity) VALUES (:order_id, :book_id, :title, :unit_price, :quantity)');

            $orderItems = [];
            foreach ($items as $it) {
                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':book_id' => $it['book_id'] ?? null,
                    ':title' => $it['title'] ?? '',
                    ':unit_price' => (int) $it['unit_price'],
                    ':quantity' => (int) $it['quantity'],
                ]);
                
                // Create OrderItem object for the created item
                $itemId = (int)$this->pdo->lastInsertId();
                $orderItems[] = new OrderItem([
                    'order_item_id' => $itemId,
                    'order_id' => $orderId,
                    'book_id' => $it['book_id'] ?? null,
                    'title' => $it['title'] ?? '',
                    'unit_price' => (int) $it['unit_price'],
                    'quantity' => (int) $it['quantity'],
                ]);
            }

            $this->pdo->commit();
            
            // Return the complete Order object
            $order = new Order([
                'order_id' => $orderId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata,
                'status' => 'paid',
            ]);
            
            foreach ($orderItems as $item) {
                $order->addItem($item);
            }
            
            return $order;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}