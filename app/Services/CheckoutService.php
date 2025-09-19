<?php
declare(strict_types=1);

namespace App\Services;

use Stripe\StripeClient;

/**
 * CheckoutService - wrapper around Stripe client for creating Checkout Sessions.
 */
final class CheckoutService
{
    private StripeClient $stripe;
    private string $currency;

    /**
     * @param StripeClient $stripe
     * @param string $currency (e.g. 'usd')
     */
    public function __construct(StripeClient $stripe, string $currency = 'usd')
    {
        $this->stripe = $stripe;
        $this->currency = $currency;
    }

    /**
     * Create a Stripe Checkout Session for given line items.
     *
     * $lineItems expected format:
     * [
     *   ['name' => 'Title', 'amount' => 1234, 'quantity' => 2, 'images' => ['https://...']],
     *   ...
     * ]
     *
     * amount is in cents.
     *
     * Returns Checkout Session object (Stripe).
     */
    public function createCheckoutSession(array $lineItems, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        // Build Stripe line_items format
        $si = [];
        foreach ($lineItems as $li) {
            $si[] = [
                'price_data' => [
                    'currency' => $this->currency,
                    'product_data' => [
                        'name' => (string) ($li['name'] ?? 'Item'),
                        'images' => $li['images'] ?? [],
                    ],
                    'unit_amount' => (int) ($li['amount'] ?? 0),
                ],
                'quantity' => max(1, (int) ($li['quantity'] ?? 1)),
            ];
        }

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $si,
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            // automatic tax/amount capture is off by default; tune as needed
            'allow_promotion_codes' => true,
        ]);

        return $session;
    }

    /**
     * Helper to verify webhook signature and parse event.
     * Throws exceptions on invalid signature or payload.
     */
    public function constructEvent(string $payload, string $sigHeader, string $endpointSecret): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
    }
}
