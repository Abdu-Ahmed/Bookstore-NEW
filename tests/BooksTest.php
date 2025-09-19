<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

final class BooksTest extends TestCase
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

public function testBooksIndex()
{
    $res = $this->client->request('GET', '/books');
    $this->assertEquals(200, $res->getStatusCode(), 'GET /books should return 200');
    
    // Don't dump the whole body - just check key indicators
    $body = (string)$res->getBody();
    $this->assertStringContainsString('<title>', $body, 'Response should contain HTML title tag');
    $this->assertStringContainsString('Shop', $body, 'Response should contain Shop content');
    
    echo "✅ Books index test passed - HTML page loaded correctly\n";
}

public function testBookDetailIfExists()
{
    $res = $this->client->request('GET', '/book-detail/14');
    $status = $res->getStatusCode();
    
    if ($status === 500) {
        // Get the error details
        $body = (string)$res->getBody();
        echo "500 Error Response Body:\n";
        echo substr($body, 0, 1000) . "\n"; // First 1000 chars
    }
    
    if ($status === 200) {
        $body = (string)$res->getBody();
        $this->assertStringContainsString('<title>', $body, 'Book detail should return HTML page');
        echo "Book detail test passed - found book with ID 1\n";
    } elseif ($status === 404) {
        echo "Book detail test passed - no book with ID 1 (404 as expected)\n";
    } else {
        $this->fail("Book detail returned unexpected status: {$status}");
    }
    
    $this->assertTrue(in_array($status, [200, 404]), "Expected 200 or 404, got {$status}");
}
}
