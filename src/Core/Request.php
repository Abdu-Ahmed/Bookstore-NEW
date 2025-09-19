<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable HTTP request representation.
 *
 * Designed to be constructed from globals (see Request::fromGlobals)
 * or directly in a factory using explicit values.
 */
final class Request
{
    private string $method;

    private string $uri;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $body;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param string               $method
     * @param string               $uri
     * @param array<string,mixed>  $query
     * @param array<string,mixed>  $body
     * @param array<string,mixed>  $server
     * @param array<string,string> $headers
     */
    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $server = [],
        array $headers = []
    ) {
        $this->method  = strtoupper($method);
        $this->uri     = $this->normalizeUri($uri);
        $this->query   = $query;
        $this->body    = $body;
        $this->server  = $server;
        $this->headers = $this->normalizeHeaders($headers);
    }

    /**
     * Create request from PHP globals.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $query  = $_GET ?? [];
        $body   = $_POST ?? [];
        $server = $_SERVER ?? [];
        $headers = function_exists('getallheaders') ? getallheaders() : self::extractHeadersFromServer($server);

        return new self($method, $uri, $query, $body, $server, $headers);
    }

    /**
     * HTTP method (GET, POST, PUT, DELETE, etc).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Request URI path (without query string).
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * All query parameters ($_GET).
     *
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * All parsed body params ($_POST).
     *
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return $this->body;
    }

    /**
     * Full server params ($_SERVER).
     *
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * All headers as lower-cased keys.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a single header value (first value) or default.
     */
    public function getHeader(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);

        return $this->headers[$key] ?? $default;
    }

    /**
     * Get a single query param.
     *
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(string $name, $default = null)
    {
        return $this->query[$name] ?? $default;
    }

    /**
     * Get a single parsed body param.
     *
     * @param mixed $default
     * @return mixed
     */
    public function getParsedBodyParam(string $name, $default = null)
    {
        return $this->body[$name] ?? $default;
    }

    /**
     * Raw request input (php://input) as string.
     */
    public function getRawInput(): string
    {
        return (string) file_get_contents('php://input');
    }

    /**
     * Normalize URI to path portion (no query string) and ensure leading slash.
     */
    private function normalizeUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . ltrim($path, '/');
    }

    /**
     * Normalize headers: lower-case keys, string values.
     *
     * @param array<string,string> $headers
     * @return array<string,string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $out = [];

        foreach ($headers as $k => $v) {
            $key = strtolower((string) $k);
            $out[$key] = is_array($v) ? (string) reset($v) : (string) $v;
        }

        return $out;
    }

    /**
     * Extract headers from $_SERVER (fallback when getallheaders is not available).
     *
     * @param array<string,mixed> $server
     * @return array<string,string>
     */
    private static function extractHeadersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr((string) $key, 5)));
                $headers[$name] = (string) $value;
            }

            if ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = (string) $value;
            }

            if ($key === 'CONTENT_LENGTH') {
                $headers['content-length'] = (string) $value;
            }
        }

        return $headers;
    }
}
