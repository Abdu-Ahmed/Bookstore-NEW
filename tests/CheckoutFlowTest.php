<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

final class CheckoutFlowTest extends TestCase
{
    private string $base;
    private Client $client;
    private CookieJar $jar;

    protected function setUp(): void
    {
        $this->base = getenv('BASE_URL') ?: 'http://localhost';
        $this->jar = new CookieJar();
        $this->client = new Client([
            'base_uri' => rtrim($this->base, '/'),
            'cookies' => $this->jar,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json, text/html;q=0.9,*/*;q=0.8',
            ],
            'timeout' => 10,
        ]);
    }

    public function testCheckoutFlowWithAuthenticatedUser()
    {
        // 1. Register a user
        $random = bin2hex(random_bytes(4));
        $username = 'phpunit_' . $random;
        $email = $username . '@example.test';
        $password = 'Passw0rd!';

        $registerRes = $this->client->request('POST', '/register', [
            'form_params' => [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'confirm_password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        $this->assertContains($registerRes->getStatusCode(), [200, 302], 'Register should return 200 or redirect');

        // 2. Login with credentials
        $loginRes = $this->client->request('POST', '/login', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        $this->assertContains($loginRes->getStatusCode(), [200, 302], 'Login should return 200 or redirect');

        // 3. Add a book to cart (assuming book ID 1 exists)
        $cartRes = $this->client->request('POST', '/cart/add/14', [
            'form_params' => [
                'quantity' => 2,
            ],
            'allow_redirects' => false,
        ]);

        $this->assertContains($cartRes->getStatusCode(), [200, 302], 'Add to cart should return 200 or redirect');

        // 4. View cart to verify item was added
        $viewCartRes = $this->client->request('GET', '/cart', ['allow_redirects' => false]);
        $this->assertEquals(200, $viewCartRes->getStatusCode(), 'Cart page should be accessible');

        // 5. Go to checkout page
        $checkoutRes = $this->client->request('GET', '/checkout', ['allow_redirects' => false]);
        $this->assertEquals(200, $checkoutRes->getStatusCode(), 'Checkout page should be accessible');

        // 6. Create checkout session (this will test the Stripe integration)
        $checkoutCreateRes = $this->client->request('POST', '/checkout/create', [
            'allow_redirects' => false,
        ]);

        $this->assertEquals(200, $checkoutCreateRes->getStatusCode(), 'Checkout create should return 200');
        
        $responseData = json_decode($checkoutCreateRes->getBody()->getContents(), true);
        $this->assertIsArray($responseData, 'Response should be JSON');
        
        // Check if we got a Stripe session URL or an error
        if (isset($responseData['error']) && $responseData['error']) {
            $this->markTestSkipped('Stripe integration not configured properly: ' . $responseData['message']);
        } else {
            $this->assertFalse($responseData['error'], 'Checkout should not return an error');
            $this->assertArrayHasKey('url', $responseData, 'Response should contain checkout URL');
            $this->assertArrayHasKey('sessionId', $responseData, 'Response should contain session ID');
            $this->assertStringContainsString('stripe.com', $responseData['url'], 'URL should be a Stripe checkout URL');
        }

        // 7. Simulate webhook call (if we have a session ID)
        if (isset($responseData['sessionId'])) {
            $webhookPayload = [
                'id' => 'evt_test_webhook',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => $responseData['sessionId'],
                        'amount_total' => 3998, // Example amount in cents
                        'currency' => 'usd',
                        'client_reference_id' => '1', // User ID
                        'metadata' => [],
                    ]
                ]
            ];

            $webhookRes = $this->client->request('POST', '/webhook/stripe', [
                'headers' => [
                    'Stripe-Signature' => 'test_signature', // This would need to be valid in a real test
                ],
                'body' => json_encode($webhookPayload),
                'allow_redirects' => false,
            ]);

            $this->assertEquals(200, $webhookRes->getStatusCode(), 'Webhook should return 200');
        }

        // 8. Check that cart is cleared after successful checkout
        $cartAfterRes = $this->client->request('GET', '/cart', ['allow_redirects' => false]);
        $cartContent = $cartAfterRes->getBody()->getContents();
        
        // Depending on implementation, cart might be empty or show a message
        $this->assertTrue(
            $cartAfterRes->getStatusCode() === 200 && 
            (strpos($cartContent, 'empty') !== false || 
             strpos($cartContent, 'Empty') !== false ||
             strpos($cartContent, 'no items') !== false),
            'Cart should be empty after checkout'
        );

        // 9. Logout
        $logoutRes = $this->client->request('POST', '/logout', ['allow_redirects' => false]);
        $this->assertContains($logoutRes->getStatusCode(), [200, 302], 'Logout should return 200 or redirect');
    }

    public function testCheckoutWithEmptyCart()
    {
        // 1. Register and login a user
        $random = bin2hex(random_bytes(4));
        $username = 'phpunit_' . $random;
        $email = $username . '@example.test';
        $password = 'Passw0rd!';

        $registerRes = $this->client->request('POST', '/register', [
            'form_params' => [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'confirm_password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        $loginRes = $this->client->request('POST', '/login', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        // 2. Try to create checkout with empty cart
        $checkoutCreateRes = $this->client->request('POST', '/checkout/create', [
            'allow_redirects' => false,
        ]);

        $this->assertEquals(400, $checkoutCreateRes->getStatusCode(), 'Empty cart should return 400');
        
        $responseData = json_decode($checkoutCreateRes->getBody()->getContents(), true);
        $this->assertIsArray($responseData, 'Response should be JSON');
        $this->assertTrue($responseData['error'], 'Response should indicate error');
        $this->assertEquals('Cart is empty', $responseData['message'], 'Error message should indicate empty cart');
    }

    public function testCheckoutPagesWithoutAuthentication()
    {
        // 1. Try to access checkout without authentication
        $checkoutRes = $this->client->request('GET', '/checkout', ['allow_redirects' => false]);
        
        // Should redirect to login or return error
        $this->assertTrue(
            in_array($checkoutRes->getStatusCode(), [302, 401, 403]),
            'Checkout should require authentication'
        );

        // 2. Try to create checkout session without authentication
        $checkoutCreateRes = $this->client->request('POST', '/checkout/create', [
            'allow_redirects' => false,
        ]);

        // Should redirect to login or return error
        $this->assertTrue(
            in_array($checkoutCreateRes->getStatusCode(), [302, 401, 403]),
            'Checkout creation should require authentication'
        );
    }
}