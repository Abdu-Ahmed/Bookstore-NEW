<?php
declare(strict_types=1);

namespace App\Models;

/**
 * CartItem entity — represents a user's cart item.
 */
final class CartItem
{
    public int $cart_item_id;
    public int $user_id;
    public int $book_id;
    public int $quantity;
    public string $book_title;
    public string $book_image;
    public float $book_price;

    public function __construct(array $data)
    {
        $this->cart_item_id = (int)($data['cart_item_id'] ?? 0);
        $this->user_id = (int)($data['user_id'] ?? 0);
        $this->book_id = (int)($data['book_id'] ?? 0);
        $this->quantity = (int)($data['quantity'] ?? 1);
        $this->book_title = (string)($data['book_title'] ?? '');
        $this->book_image = (string)($data['book_image'] ?? '');
        $this->book_price = (float)($data['book_price'] ?? 0.0);
    }

    public function subtotal(): float
    {
        return $this->quantity * $this->book_price;
    }
}
