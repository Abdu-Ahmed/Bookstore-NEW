<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\View;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;

/**
 * Base controller with helpers for rendering, JSON, redirects and auth
 */
abstract class Controller
{
    protected Container $container;
    protected View $view;
    protected Request $request;
    protected Response $response;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->view = $container->get('view');
        $this->request = $container->get(Request::class);
        $this->response = $container->get(Response::class);
    }

    /**
     * Get minicart data for the current user session
     */
    protected function getMinicartData(): array
    {
        $helperPath = dirname(__DIR__) . '/Support/minicart.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
            if (function_exists('get_minicart_data')) {
                $data = get_minicart_data();
                return [
                    'count' => $data['count'] ?? 0,
                    'subtotal' => $data['total'] ?? 0.0,
                    'items' => $data['items'] ?? []
                ];
            }
        }
        return ['count' => 0, 'subtotal' => 0.0, 'items' => []];
    }

    /**
     * Check if user is currently logged in
     */
    protected function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check session
        if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
            return true;
        }

        // Try refresh token
        $refreshToken = $_COOKIE['refresh_token'] ?? $_SESSION['refresh_token'] ?? null;
        
        if ($refreshToken) {
            try {
                $authService = $this->container->get(\App\Services\AuthService::class);
                $authResult = $authService->refreshToken($refreshToken);
                
                if ($authResult && isset($authResult['user'])) {
                    // Restore session
                    $_SESSION['user_id'] = $authResult['user']['id'];
                    $_SESSION['username'] = $authResult['user']['username'];
                    $_SESSION['email'] = $authResult['user']['email'] ?? '';
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    return true;
                }
            } catch (\Throwable $e) {
                // Clear invalid token
                unset($_SESSION['refresh_token']);
                setcookie('refresh_token', '', time() - 3600, '/');
            }
        }

        return false;
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return (int) $_SESSION['user_id'];
    }

    /**
     * Get current user data
     */
    protected function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email' => $_SESSION['email'] ?? '',
        ];
    }

    /**
     * Require authentication - redirect to login if not logged in
     */
    protected function requireAuth(): ?Response
    {
        if (!$this->isLoggedIn()) {
            $baseUrl = $this->container->get('settings')['app']['base_path'] ?? '';
            return $this->redirect($baseUrl . '/login');
        }
        return null;
    }

    /**
     * Render an HTML view and return a Response instance.
     * Now automatically includes user auth data and minicart data.
     */
    protected function view(string $template, array $params = [], int $status = 200): Response
    {
        // Add minicart data if not already present
        if (!isset($params['minicart'])) {
            $params['minicart'] = $this->getMinicartData();
        }

        // Add user auth data
        if (!isset($params['current_user'])) {
            $params['current_user'] = $this->getCurrentUser();
        }
        
        if (!isset($params['is_logged_in'])) {
            $params['is_logged_in'] = $this->isLoggedIn();
        }

        // Ensure base_url is available
        if (!isset($params['base_url'])) {
            $params['base_url'] = $this->container->get('settings')['app']['base_path'] ?? '';
        }

        $html = $this->view->render($template, $params);
        return $this->response->html($html, $status);
    }

    /**
     * Shortcut to return JSON response.
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return $this->response->json($data, $status);
    }

    /**
     * Shortcut to create redirect response.
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return $this->response->redirect($url, $status);
    }

    /**
     * Normalize a Book model or array to an associative array for views.
     *
     * Accepts:
     *  - arrays (returned unchanged)
     *  - objects with toArray() -> use that
     *  - objects with public properties -> use get_object_vars()
     *  - other objects -> cast to (array)
     *
     * Keeps original keys where possible (e.g. book_title, book_price, book_image).
     *
     * @param mixed $b
     * @return array<string,mixed>
     */
    private function normalizeBook(mixed $b): array
    {
        if (is_array($b)) {
            return $b;
        }

        if (is_object($b)) {
            // prefer explicit toArray
            if (method_exists($b, 'toArray')) {
                try {
                    $arr = $b->toArray();
                    if (is_array($arr)) {
                        return $arr;
                    }
                } catch (\Throwable $_) {
                    // continue to other fallbacks
                }
            }

            // public properties
            $vars = get_object_vars($b);
            if (!empty($vars)) {
                return $vars;
            }

            // last resort
            return (array) $b;
        }

        return [];
    }

    /**
     * Normalize an array of book-like items.
     *
     * @param iterable $items
     * @return array<int,array<string,mixed>>
     */
    private function normalizeBooks(iterable $items): array
    {
        $out = [];
        foreach ($items as $it) {
            $out[] = $this->normalizeBook($it);
        }
        return $out;
    }
}
