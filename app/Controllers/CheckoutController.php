<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\CartService;
use App\Models\Order;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Handles checkout process, Stripe session creation, and webhook processing
 */
final class CheckoutController extends Controller
{
    private CheckoutService $checkoutService;
    private OrderService $orderService;
    private CartService $cartService;
    protected Container $container;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->container = $container;
        $this->checkoutService = $container->get(CheckoutService::class);
        $this->orderService = $container->get(OrderService::class);
        $this->cartService = $container->get(CartService::class);
    }

    /**
     * Display checkout summary page
     */
    public function index(Request $request): Response
    {
        $userId = $this->getCurrentUserId();
        $cartItems = $this->cartService->getItemsByUser($userId);
        $minicart = $this->buildMiniCart($cartItems);

        $settings = $this->container->get('settings');
        $stripePublishable = $settings['stripe']['publishable_key'] ?? '';

        return $this->view('checkout/summary', [
            'cartItems' => $cartItems,
            'minicart' => $minicart,
            'stripe_publishable' => $stripePublishable,
            'base_url' => $settings['app']['base_url'] ?? '',
        ]);
    }

 /**
 * Create Stripe Checkout session and return session details
 */
public function create(Request $request): Response
{
    $userId = $this->getCurrentUserId();
    $cartItems = $this->cartService->getItemsByUser($userId);
    
    if (empty($cartItems)) {
        return $this->json(['error' => true, 'message' => 'Cart is empty'], 400);
    }

    $lineItems = array_map(function ($item) {
        return [
            'name' => $item->book_title,
            'amount' => (int)($item->book_price * 100),
            'quantity' => $item->quantity,
            'images' => [$item->book_image],
        ];
    }, $cartItems);

    $settings = $this->container->get('settings');
    $baseUrl = $settings['app']['base_url'] ?? 'http://localhost';
    $successUrl = $baseUrl . '/checkout/success';
    $cancelUrl = $baseUrl . '/checkout/cancel';

    try {
        $session = $this->checkoutService->createCheckoutSession($lineItems, $successUrl, $cancelUrl);
        
        return $this->json([
            'error' => false,
            'url' => $session->url ?? '',
            'sessionId' => $session->id ?? ''
        ]);

    } catch (ApiErrorException $e) {
        return $this->json(['error' => true, 'message' => 'Failed to create checkout session'], 500);
    }
}

    /**
     * Display success page after successful payment
     */
    public function success(Request $request): Response
    {
        $sessionId = (string)($request->getQueryParams()['session_id'] ?? '');
        $order = $this->orderService->findBySessionId($sessionId);
        
        if ($order) {
            $this->cartService->clearCart($this->getCurrentUserId());
        }

        return $this->view('checkout/success', [
            'order' => $order,
            'session_id' => $sessionId
        ]);
    }

    /**
     * Display cancel page when payment is canceled
     */
    public function cancel(Request $request): Response
    {
        return $this->view('checkout/cancel', ['message' => 'Your payment was canceled.']);
    }

    /**
     * Process Stripe webhook events
     */
    public function webhook(Request $request): Response
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $settings = $this->container->get('settings');
        $webhookSecret = $settings['stripe']['webhook_secret'] ?? '';
        
        try {
            $event = $webhookSecret 
                ? $this->checkoutService->constructEvent($payload, $sigHeader, $webhookSecret)
                : new \Stripe\Event(json_decode($payload, true));

            if ($event->type === 'checkout.session.completed') {
                $this->handleCompletedCheckoutSession($event->data->object);
            }

            return $this->json(['received' => true], 200);

        } catch (\Throwable $e) {
            error_log('[Webhook] Error: ' . $e->getMessage());
            return $this->json(['error' => true, 'message' => 'Invalid payload or signature'], 400);
        }
    }

    /**
     * Handle completed checkout session event
     */
    private function handleCompletedCheckoutSession(object $session): void
    {
        try {
            $lineItems = $this->retrieveSessionLineItems($session->id);
            $this->orderService->createFromStripeSession(
                $session->id,
                (array)$session,
                $lineItems
            );
        } catch (\Throwable $e) {
            error_log('[Webhook] Error creating order: ' . $e->getMessage());
        }
    }

    /**
     * Build mini cart data for display
     * 
     * @param array $cartItems
     * @return array
     */
    private function buildMiniCart(array $cartItems): array
    {
        $count = 0;
        $subtotal = 0.0;
        $preview = [];
        
        foreach ($cartItems as $item) {
            $count += $item->quantity;
            $subtotal += $item->quantity * $item->book_price;
            
            if (count($preview) < 4) {
                $preview[] = [
                    'book_id' => $item->book_id,
                    'title' => $item->book_title,
                    'image' => $item->book_image,
                    'price' => $item->book_price,
                    'quantity' => $item->quantity,
                ];
            }
        }
        
        return [
            'count' => $count,
            'subtotal' => $subtotal,
            'items' => $preview
        ];
    }

    /**
     * Retrieve current user ID from session
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Retrieve line items for a Stripe session
     */
    private function retrieveSessionLineItems(string $sessionId): array
    {
        $settings = $this->container->get('settings');
        $stripeSecret = $settings['stripe']['secret_key'] ?? '';
        
        if (!$stripeSecret) {
            throw new \RuntimeException('Stripe secret key not configured');
        }
        
        $stripe = new StripeClient($stripeSecret);
        $lineItems = $stripe->checkout->sessions->allLineItems($sessionId, ['limit' => 100]);
        
        $items = [];
        foreach ($lineItems->autoPagingIterator() as $item) {
            $items[] = [
                'price_data' => [
                    'unit_amount' => $item->price->unit_amount,
                    'product_data' => [
                        'name' => $item->price->product->name,
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }
        
        return $items;
    }
}