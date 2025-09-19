<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Simple validator service with fluent rules.
 */
final class ValidatorService
{
    /** @var array<string,string> */
    private array $errors = [];

    public function reset(): self
    {
        $this->errors = [];
        return $this;
    }

    public function required(string $field, $value): self
    {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            $this->errors[$field] = sprintf('%s is required.', $field);
        }
        return $this;
    }

    public function length(string $field, string $value, int $min, int $max): self
    {
        $len = mb_strlen($value);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = sprintf('%s must be between %d and %d characters.', $field, $min, $max);
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format.';
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
