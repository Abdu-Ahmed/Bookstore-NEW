<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\CartItem;
use PDO;

/**
 * CartRepository - PDO-backed data access for cart items.
 */
final class CartRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Add or increment an item in the cart.
     */
    public function addItem(int $userId, int $bookId, int $quantity): void
    {
        $sql = 'INSERT INTO cart_items (user_id, book_id, quantity)
                VALUES (:user_id, :book_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + :quantity_update';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
            ':quantity' => $quantity,
            ':quantity_update' => $quantity,
        ]);
    }

    /**
     * Get cart items for a user with book details.
     *
     * @return array<int,CartItem>
     */
    public function getItemsByUser(int $userId): array
    {
        $sql = 'SELECT ci.cart_item_id, ci.user_id, ci.book_id, ci.quantity,
                       b.book_title, b.book_image, b.book_price
                FROM cart_items ci
                INNER JOIN books b ON ci.book_id = b.book_id
                WHERE ci.user_id = :user_id
                ORDER BY ci.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cartItems = [];
        foreach ($rows as $row) {
            $cartItems[] = new CartItem($row);
        }
        
        return $cartItems;
    }

    /**
     * Find a specific cart item by ID.
     */
    public function findById(int $cartItemId): ?CartItem
    {
        $sql = 'SELECT ci.cart_item_id, ci.user_id, ci.book_id, ci.quantity,
                       b.book_title, b.book_image, b.book_price
                FROM cart_items ci
                INNER JOIN books b ON ci.book_id = b.book_id
                WHERE ci.cart_item_id = :id
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $cartItemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? new CartItem($row) : null;
    }

    public function updateQuantity(int $cartItemId, int $quantity): void
    {
        $sql = 'UPDATE cart_items SET quantity = :quantity WHERE cart_item_id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':quantity' => $quantity, ':id' => $cartItemId]);
    }

    public function removeItem(int $cartItemId): void
    {
        $sql = 'DELETE FROM cart_items WHERE cart_item_id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $cartItemId]);
    }

    public function clearCart(int $userId): void
    {
        $sql = 'DELETE FROM cart_items WHERE user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }
}