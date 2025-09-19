<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

final class AuthFlowTest extends TestCase
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
            // set a timeout so tests fail fast if server unreachable
            'timeout' => 10,
        ]);
    }

    public function testHomepageIsReachable()
    {
        $res = $this->client->request('GET', '/');
        $this->assertContains($res->getStatusCode(), [200, 302], 'Homepage should respond 200 or redirect');
        // optionally assert something about body if needed
    }

    public function testRegisterLoginAndProtectedRoute()
    {
        // 1) Register a fresh random user
        $random = bin2hex(random_bytes(4));
        $username = 'phpunit_' . $random;
        $email = $username . '@example.test';
        $password = 'Passw0rd!';

        // POST register
        $registerRes = $this->client->request('POST', '/register', [
            'form_params' => [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'confirm_password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        // Accept either 302 redirect to login OR 200 page
        $this->assertContains($registerRes->getStatusCode(), [200, 302], 'Register should return 200 or redirect');

        // 2) Login with credentials
        $loginRes = $this->client->request('POST', '/login', [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ],
            'allow_redirects' => false,
        ]);

        // Expect a redirect after successful login (302) or 200
        $this->assertContains($loginRes->getStatusCode(), [200, 302], 'Login should return 200 or redirect');

        // Check that session cookie exists in jar (PHPSESSID typical)
        $cookies = iterator_to_array($this->jar->getIterator());
        $cookieNames = array_map(function ($c) { return $c->getName(); }, $cookies);
        $this->assertTrue(count($cookies) > 0, 'Cookie jar should contain at least one cookie after login');

        // 3) Access a protected route (cart). Should be accessible (200) and not redirect to login
        $cartRes = $this->client->request('GET', '/cart', ['allow_redirects' => false]);
        $status = $cartRes->getStatusCode();

        // If app redirects to login for unauthenticated requests, status would be 302.
        // We expect auth persisted, so allow 200 (ok) or possibly 200/302 depending on implementation.
        $this->assertNotEquals(302, $status, 'Accessing /cart should not redirect to login if authenticated');
        $this->assertContains($status, [200, 403, 404], "Cart response expected (200/403/404). Got {$status}");

        // 4) Attempt logout
        $logoutRes = $this->client->request('POST', '/logout', ['allow_redirects' => false]);
        $this->assertContains($logoutRes->getStatusCode(), [200, 302], 'Logout should return 200 or redirect');

        // After logout, accessing protected route should redirect to login or return 403
        $afterRes = $this->client->request('GET', '/cart', ['allow_redirects' => false]);
        $this->assertTrue(in_array($afterRes->getStatusCode(), [302, 403, 401]), 'After logout /cart should not be accessible');
    }
}
