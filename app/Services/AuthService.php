<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\RefreshTokenRepository;
use Firebase\JWT\JWT;
use DomainException;

/**
 * AuthService
 *
 * Handles registration, login, token creation and refresh token management.
 */
final class AuthService
{
    private UserRepository $users;
    private RefreshTokenRepository $refreshTokens;
    /** @var array<string,mixed> */
    private array $settings;

    public function __construct(UserRepository $users, RefreshTokenRepository $refreshTokens, array $settings = [])
    {
        $this->users = $users;
        $this->refreshTokens = $refreshTokens;
        $this->settings = $settings;
    }

    /**
     * Register a new user. Returns inserted user id.
     *
     * @throws DomainException on business validation errors
     */
    public function register(string $username, string $email, string $password): int
    {
        // Check if user exists - now returns User objects
        if ($this->users->findByUsername($username) !== null) {
            throw new DomainException('Username already taken.');
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new DomainException('Email already registered.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        return $this->users->create($username, $email, $hash);
    }


    /**
     * Attempt login. Returns array with access_token, refresh_token and user.
     *
     * @throws DomainException on invalid credentials
     */
    public function login(string $username, string $password): array
    {
        $user = $this->users->findByUsername($username);
        if ($user === null) {
            throw new DomainException('Invalid username or password.');
        }

        if (!password_verify($password, $user->password_hash)) {
            throw new DomainException('Invalid username or password.');
        }

        $accessToken = $this->createJwt($user->id);
        $refreshToken = $this->createRefreshToken($user->id);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $user->toArray(),
        ];
    }

    /**
     * Create JWT access token using firebase/php-jwt.
     */
    private function createJwt(int $userId): string
    {
        $jwtKey = $this->settings['jwt_secret'] ?? ($_ENV['JWT_SECRET'] ?? 'change_me');
        $issuer = $this->settings['jwt_issuer'] ?? ($_ENV['APP_URL'] ?? 'http://localhost');
        $ttl = (int) ($this->settings['access_ttl'] ?? 900); // 15 minutes default

        $now = time();
        $payload = [
            'iss' => $issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => (string) $userId,
        ];

        return JWT::encode($payload, $jwtKey, 'HS256');
    }

    /**
     * Create and persist a refresh token (returns raw token).
     */
    private function createRefreshToken(int $userId): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $ttl = (int) ($this->settings['refresh_ttl'] ?? (60 * 60 * 24 * 30)); // 30 days default
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        // repository expected to accept create(userId, hash, expiresAt)
        if (method_exists($this->refreshTokens, 'create')) {
            $this->refreshTokens->create($userId, $hash, $expiresAt);
        } elseif (method_exists($this->refreshTokens, 'store')) {
            $this->refreshTokens->store($userId, $hash, $expiresAt);
        } else {
            // best-effort: try generic createRow if present
            if (method_exists($this->refreshTokens, 'insert')) {
                $this->refreshTokens->insert(['user_id' => $userId, 'token_hash' => $hash, 'expires_at' => $expiresAt]);
            }
        }

        return $raw;
    }

    /**
     * Revoke a refresh token by raw token string.
     */
    public function revokeRefreshToken(string $rawToken): void
    {
        $hash = hash('sha256', $rawToken);
        if (method_exists($this->refreshTokens, 'revokeByHash')) {
            $this->refreshTokens->revokeByHash($hash);
            return;
        }
        if (method_exists($this->refreshTokens, 'deleteByHash')) {
            $this->refreshTokens->deleteByHash($hash);
            return;
        }
        if (method_exists($this->refreshTokens, 'delete')) {
            $this->refreshTokens->delete($hash);
            return;
        }
    }

    /**
     * Refresh an existing refresh token (rotate and return new tokens + user).
     *
     * Returns array with keys: access_token, refresh_token, user
     *
     * @throws DomainException on invalid/expired token
     */
    public function refreshToken(string $rawToken): array
    {
        $hash = hash('sha256', $rawToken);

        // Try multiple common repository method names to find the token record
        $tokenRow = null;
        $findMethods = [
            'findByHash', 'getByHash', 'findByTokenHash', 'findByToken', 'findByRefreshHash', 'find', 'get'
        ];
        foreach ($findMethods as $m) {
            if (method_exists($this->refreshTokens, $m)) {
                $tokenRow = $this->refreshTokens->$m($hash);
                if ($tokenRow) {
                    break;
                }
            }
        }

        if (empty($tokenRow)) {
            throw new DomainException('Invalid refresh token.');
        }

        // determine expiry field
        $expiresAt = $tokenRow['expires_at'] ?? $tokenRow['expires'] ?? $tokenRow['expiresAt'] ?? null;
        if ($expiresAt !== null) {
            $ts = is_numeric($expiresAt) ? (int)$expiresAt : strtotime((string)$expiresAt);
            if ($ts !== false && $ts < time()) {
                // revoke the token
                $this->revokeRefreshToken($rawToken);
                throw new DomainException('Refresh token expired.');
            }
        }

        // get user id from token row
        $userId = (int) ($tokenRow['user_id'] ?? $tokenRow['uid'] ?? $tokenRow['user'] ?? 0);
        if ($userId <= 0) {
            throw new DomainException('Refresh token corrupted.');
        }

        // fetch user record (try multiple common repository method names)
        $user = null;
        $userMethods = ['findById', 'find', 'getById', 'findOne', 'get'];
        foreach ($userMethods as $um) {
            if (method_exists($this->users, $um)) {
                $user = $this->users->$um($userId);
                if ($user) {
                    break;
                }
            }
        }

        if (empty($user)) {
            throw new DomainException('User not found for refresh token.');
        }

        // rotate: create new refresh token and revoke old one
        $newRaw = $this->createRefreshToken($userId);
        try {
            $this->revokeRefreshToken($rawToken);
        } catch (\Throwable $e) {
            // ignore revoke failure - best effort
        }

        $access = $this->createJwt($userId);

        return [
            'access_token' => $access,
            'refresh_token' => $newRaw,
            'user' => $user,
        ];
    }

    /**
     * For controllers: expose TTL for cookie lifetime.
     */
    public function getRefreshTTLSeconds(): int
    {
        return (int) ($this->settings['refresh_ttl'] ?? (60 * 60 * 24 * 30));
    }
}
