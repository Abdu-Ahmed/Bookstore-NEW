<?php
declare(strict_types=1);

namespace App\Models;

final class User
{
    public ?int $id;
    public string $username;
    public string $email;
    public string $password_hash;
    public string $role;
    public ?string $created_at;

    public function __construct(
        ?int $id,
        string $username,
        string $email,
        string $password_hash,
        string $role = 'user',
        ?string $created_at = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->role = $role;
        $this->created_at = $created_at;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            (string)($row['username'] ?? ''),
            (string)($row['email'] ?? ''),
            (string)($row['password_hash'] ?? ($row['password'] ?? '')),
            (string)($row['role'] ?? 'user'),
            $row['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}