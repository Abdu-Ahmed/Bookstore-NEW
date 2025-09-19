<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

final class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $settings = $this->container->get('settings');
        $stripeCfg = $settings['stripe'] ?? [];
        $webhookSecret = $stripeCfg['webhook_secret'] ?? getenv('STRIPE_WEBHOOK_SECRET');
        $secret = $stripeCfg['secret'] ?? getenv('STRIPE_SECRET');

        // read raw body
        $payload = @file_get_contents('php://input') ?: '';
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (empty($webhookSecret)) {
            $this->container->get('logger')->warning('Stripe webhook secret not configured.');
            return $this->json(['error' => true, 'message' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return $this->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return $this->json(['error' => 'Invalid signature'], 400);
        }

        // Ensure Stripe API key is set for subsequent retrievals
        if (!empty($secret)) {
            Stripe::setApiKey($secret);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                // retrieve expanded session with line_items for safety
                try {
                    $expanded = StripeSession::retrieve($session->id, ['expand' => ['line_items']]);
                } catch (\Throwable $e) {
                    $this->container->get('logger')->error('Failed to retrieve session: ' . $e->getMessage());
                    return $this->json(['error' => true], 500);
                }

                $sessionData = $expanded->toArray();
                $lineItems = $sessionData['line_items']['data'] ?? [];

                // persist order using OrderService (idempotent)
                try {
                    $orderService = $this->container->get(\App\Services\OrderService::class);
                    $orderId = $orderService->createFromStripeSession($session->id, $sessionData, $lineItems);
                    $this->container->get('logger')->info("Order created from session {$session->id}, order_id={$orderId}");
                } catch (\Throwable $e) {
                    $this->container->get('logger')->error('Order create failed: ' . $e->getMessage());
                    // Do not return error 400 — respond 200 so Stripe won't keep retrying if you can't recover, but you can choose 500 to force retry.
                    return $this->json(['error' => true], 500);
                }

                break;

            // other event types you care about
            case 'payment_intent.succeeded':
                $pi = $event->data->object;
                $this->container->get('logger')->info('Payment intent succeeded: ' . ($pi->id ?? ''));
                break;

            default:
                $this->container->get('logger')->info('Unhandled Stripe event: ' . $event->type);
        }

        return $this->json(['received' => true]);
    }
}
