<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\User;

final class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $sql = 'INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
                VALUES (:username, :email, :password_hash, :role, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => 'user',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find user by username - returns User object
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, password_hash, role, created_at FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : User::fromArray($row);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : User::fromArray($row);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, password_hash, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : User::fromArray($row);
    }
}