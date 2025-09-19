<?php
declare(strict_types=1);

namespace App\Core;

/**
 * DI container for wiring closures and shared services.
 */
class Container
{
    /** @var array<string, mixed> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    /**
     * Register a service or factory.
     *
     * @param string $key
     * @param mixed $value Closure(Container): mixed or any value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->definitions[$key] = $value;
    }

    /**
     * Resolve a service.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        if (!array_key_exists($key, $this->definitions)) {
            throw new \RuntimeException("Service '{$key}' not found in container.");
        }

        $def = $this->definitions[$key];
        $value = is_callable($def) ? $def($this) : $def;
        $this->resolved[$key] = $value;
        return $value;
    }

    /**
     * Check whether a service definition exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->definitions) || array_key_exists($key, $this->resolved);
    }
}
