<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * RefreshTokenRepository
 *
 * Stores token hashes for rotation/revocation.
 */
final class RefreshTokenRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        $sql = 'INSERT INTO refresh_tokens (user_id, token_hash, expires_at, revoked, created_at)
                VALUES (:user_id, :token_hash, :expires_at, 0, NOW())';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    public function revokeByHash(string $tokenHash): void
    {
        $sql = 'UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = :hash';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':hash' => $tokenHash]);
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $sql = 'SELECT * FROM refresh_tokens WHERE token_hash = :hash AND revoked = 0 AND expires_at > NOW() LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
