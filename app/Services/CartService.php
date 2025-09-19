<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\CartItem;
use App\Repositories\CartRepository;

/**
 * CartService - Business logic for cart operations using model objects
 */
final class CartService
{
    private CartRepository $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * Add item to user's cart
     */
    public function addItem(int $userId, int $bookId, int $quantity): void
    {
        $this->cartRepository->addItem($userId, $bookId, $quantity);
    }

    /**
     * Get user's cart items
     * @return CartItem[]
     */
    public function getItemsByUser(int $userId): array
    {
        return $this->cartRepository->getItemsByUser($userId);
    }

    /**
     * Get cart summary with totals
     */
    public function getCartSummary(int $userId): array
    {
        $items = $this->cartRepository->getItemsByUser($userId);
        
        $totalQuantity = 0;
        $totalAmount = 0.0;
        
        foreach ($items as $item) {
            $totalQuantity += $item->quantity;
            $totalAmount += $item->subtotal();
        }

        return [
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
            'item_count' => count($items),
        ];
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(int $cartItemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->cartRepository->removeItem($cartItemId);
        } else {
            $this->cartRepository->updateQuantity($cartItemId, $quantity);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartItemId): void
    {
        $this->cartRepository->removeItem($cartItemId);
    }

    /**
     * Clear user's entire cart
     */
    public function clearCart(int $userId): void
    {
        $this->cartRepository->clearCart($userId);
    }

    /**
     * Find specific cart item by ID
     */
    public function findCartItem(int $cartItemId): ?CartItem
    {
        return $this->cartRepository->findById($cartItemId);
    }

    /**
     * Validate cart item belongs to user
     */
    public function validateCartItemOwnership(int $cartItemId, int $userId): bool
    {
        $item = $this->cartRepository->findById($cartItemId);
        return $item !== null && $item->user_id === $userId;
    }
}