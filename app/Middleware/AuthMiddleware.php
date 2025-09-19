<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Services\AuthService;

/**
 * AuthMiddleware - handles authentication for protected routes
 * 
 * Returns null to continue to controller, or Response to short-circuit (redirect)
 */
final class AuthMiddleware
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle authentication check
     * 
     * @param Request $request
     * @return Response|null Return Response to redirect, null to continue
     */
    public function handle(Request $request): ?Response
    {
        
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in via session
        if ($this->isLoggedInViaSession()) {
            return null; // Continue to controller
        }

        // Try to authenticate via refresh token
        if ($this->tryRefreshAuthentication()) {
            return null; // Continue to controller
        }

        // Not authenticated - redirect to login
        return $this->redirectToLogin();
    }

    /**
     * Check if user is logged in via session
     */
    private function isLoggedInViaSession(): bool
    {
        return isset($_SESSION['logged_in'], $_SESSION['user_id']) 
            && $_SESSION['logged_in'] === true 
            && is_numeric($_SESSION['user_id'])
            && (int)$_SESSION['user_id'] > 0;
    }

    /**
     * Try to authenticate using refresh token
     */
    private function tryRefreshAuthentication(): bool
    {
        // Check for refresh token in cookie or session
        $refreshToken = $_COOKIE['refresh_token'] ?? $_SESSION['refresh_token'] ?? null;
        
        if (!$refreshToken || !is_string($refreshToken)) {
            return false;
        }

        try {
            // Get AuthService from container
            $authService = $this->container->get(AuthService::class);
            $result = $authService->refreshToken($refreshToken);
            
            if ($result && isset($result['user']) && is_array($result['user'])) {
                // Restore session with user data
                $user = $result['user'];
                $_SESSION['user_id'] = (int)($user['id'] ?? 0);
                $_SESSION['username'] = (string)($user['username'] ?? '');
                $_SESSION['email'] = (string)($user['email'] ?? '');
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update refresh token cookie if we got a new one
                if (isset($result['refresh_token'])) {
                    $ttl = $this->getRefreshTokenTTL();
                    setcookie(
                        'refresh_token', 
                        $result['refresh_token'], 
                        time() + $ttl, 
                        '/', 
                        '', 
                        $this->isSecureConnection(), 
                        true
                    );
                }
                
                return true;
            }
        } catch (\Throwable $e) {
            // Clear invalid tokens on any error
            $this->clearAuthTokens();
        }

        return false;
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(): Response
    {
        try {
            $settings = $this->container->get('settings');
            $baseUrl = $settings['app']['base_path'] ?? '';
        } catch (\Throwable $e) {
            $baseUrl = '';
        }

        $loginUrl = $baseUrl . '/login';
        
        // For API requests, return JSON instead of redirect
        if ($this->isApiRequest()) {
            return Response::json([
                'error' => true, 
                'message' => 'Authentication required',
                'login_url' => $loginUrl
            ], 401);
        }

        return Response::redirect($loginUrl);
    }

    /**
     * Get refresh token TTL from AuthService
     */
    private function getRefreshTokenTTL(): int
    {
        try {
            $authService = $this->container->get(AuthService::class);
            return $authService->getRefreshTTLSeconds();
        } catch (\Throwable $e) {
            return 60 * 60 * 24 * 30; // Default 30 days
        }
    }

    /**
     * Check if this is a secure HTTPS connection
     */
    private function isSecureConnection(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }

    /**
     * Check if this is an API request (expects JSON response)
     */
    private function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        return str_contains($accept, 'application/json') 
            || str_contains($contentType, 'application/json')
            || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    /**
     * Clear authentication tokens
     */
    private function clearAuthTokens(): void
    {
        // Clear session
        unset($_SESSION['refresh_token'], $_SESSION['logged_in'], $_SESSION['user_id']);
        
        // Clear cookie
        setcookie('refresh_token', '', time() - 3600, '/', '', false, true);
    }
}