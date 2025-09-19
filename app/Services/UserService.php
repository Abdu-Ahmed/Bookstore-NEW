<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

/**
 * Domain logic for users (registration, lookup).
 */
final class UserService
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function register(string $username, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        return $this->repo->create($username, $email, $hash);
    }

    public function verifyCredentials(string $username, string $password): ?array
    {
        $user = $this->repo->findByUsername($username);
        if ($user === null) {
            return null;
        }

        if (!isset($user['password']) || !password_verify($password, (string) $user['password'])) {
            return null;
        }

        return $user;
    }
}
