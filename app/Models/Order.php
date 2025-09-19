<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Order entity — represents a purchase.
 */
final class Order
{
    public int $order_id;
    public ?int $user_id;
    public string $session_id;
    public int $amount;
    public string $currency;
    public ?array $metadata;
    public string $status;

    /** @var array<int,OrderItem> */
    public array $items = [];

    public function __construct(array $data)
    {
        $this->order_id = (int)($data['order_id'] ?? 0);
        $this->user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->session_id = (string)($data['session_id'] ?? '');
        $this->amount = (int)($data['amount'] ?? 0);
        $this->currency = (string)($data['currency'] ?? 'usd');
        $this->metadata = isset($data['metadata'])
            ? (is_array($data['metadata']) ? $data['metadata'] : json_decode((string)$data['metadata'], true))
            : null;
        $this->status = (string)($data['status'] ?? 'pending');
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function totalQuantity(): int
    {
        return array_sum(array_map(fn($i) => $i->quantity, $this->items));
    }
}
