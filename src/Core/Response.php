<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

/**
 * HTTP response value object with helpers for JSON / HTML responses.
 *
 * Immutable style: methods that change state return a cloned instance.
 */
final class Response
{
    private int $status;

    private string $body;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param string               $body
     * @param int                  $status
     * @param array<string,string> $headers
     */
    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->status  = $status;
        $this->body    = $body;
        $this->headers = $headers;
    }

        /**
     * Create a redirect response (Location header).
     *
     * Returns a Response instance with the Location header set.
     *
     * @param string $url    Absolute or relative URL to redirect to
     * @param int    $status 3xx HTTP status code (302 by default)
     */
    public static function redirect(string $url, int $status = 302): self
    {
        // Ensure status is a redirect code
        if ($status < 300 || $status >= 400) {
            $status = 302;
        }

        $response = new self('', $status, ['Location' => $url]);

        return $response;
    }

    /**
     * Get HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Get response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get response headers.
     *
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Return a new instance with given status code.
     */
    public function withStatus(int $status): self
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    /**
     * Return a new instance with added/overwritten header.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Return a new instance with a new body.
     */
    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * Send HTTP response to the client (headers + body).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', (string) $name, (string) $value));
            }
        }

        echo $this->body;
    }

    /**
     * Create an HTML response.
     */
    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data
     *
     * @throws JsonException
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $response = new self($json, $status, ['Content-Type' => 'application/json; charset=utf-8']);

        return $response;
    }

    /**
     * Convenience: emit an error JSON immediately and exit.
     *
     * This matches prior usage where code called App\Response::error(...).
     *
     * NOTE: calling this will echo and exit.
     *
     * @param int   $status
     * @param string $message
     * @param array<string,mixed> $extra
     *
     * @return void
     *
     * @throws JsonException
     */
    public static function error(int $status, string $message, array $extra = []): void
    {
        $payload = array_merge(['error' => true, 'message' => $message], $extra);

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit(1);
    }
}
