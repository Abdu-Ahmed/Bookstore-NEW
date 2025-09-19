<?php
declare(strict_types=1);

namespace App\Models;

/**
 * OrderItem entity — represents a purchased line item.
 */
final class OrderItem
{
    public int $order_item_id;
    public int $order_id;
    public ?int $book_id;
    public string $title;
    public int $unit_price;
    public int $quantity;

    public function __construct(array $data)
    {
        $this->order_item_id = (int)($data['order_item_id'] ?? 0);
        $this->order_id = (int)($data['order_id'] ?? 0);
        $this->book_id = isset($data['book_id']) ? (int)$data['book_id'] : null;
        $this->title = (string)($data['title'] ?? '');
        $this->unit_price = (int)($data['unit_price'] ?? 0);
        $this->quantity = (int)($data['quantity'] ?? 1);
    }

    public function subtotal(): int
    {
        return $this->quantity * $this->unit_price;
    }
}
