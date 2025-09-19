<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Services\AuthService;
use App\Services\ValidatorService;
use App\Core\Controller;

/**
 * AuthController with improved session persistence
 */
final class AuthController extends Controller
{
    private AuthService $authService;
    private ValidatorService $validator;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->authService = $container->get(AuthService::class);
        $this->validator = $container->get(ValidatorService::class);
    }

    /**
     * Show and handle registration form.
     */
    public function register(Request $request): Response
    {
        $baseUrl = (string)($this->container->get('settings')['app']['base_path'] ?? '');
        $errors = [];
        $data = [
            'username' => '',
            'email' => '',
            'base_url' => $baseUrl,
        ];

        if ($request->method() === 'POST') {
            $username = (string) $request->getParsedBodyParam('username', '');
            $email = (string) $request->getParsedBodyParam('email', '');
            $password = (string) $request->getParsedBodyParam('password', '');
            $confirm = (string) $request->getParsedBodyParam('confirm_password', '');

            $this->validator->reset()
                ->required('username', $username)
                ->length('username', $username, 2, 50)
                ->required('email', $email)
                ->email('email', $email)
                ->required('password', $password)
                ->length('password', $password, 6, 255);

            if ($password !== $confirm) {
                $this->validator->addError('confirm_password', 'Passwords do not match.');
            }

            if ($this->validator->fails()) {
                $errors = $this->validator->errors();
            } else {
                try {
                    $userId = $this->authService->register($username, $email, $password);
                    return $this->redirect($baseUrl . '/login?registered=1');
                } catch (\DomainException $e) {
                    $errors['general'] = $e->getMessage();
                }
            }

            $data['username'] = $this->validator->sanitize($username);
            $data['email'] = $this->validator->sanitize($email);
        }

        return $this->view('user/account', [
            'errors' => $errors,
            'data' => $data,
        ]);
    }

    /**
     * Show and handle login form.
     */
    public function login(Request $request): Response
    {
        // Ensure session started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $baseUrl = (string)($this->container->get('settings')['app']['base_path'] ?? '');
        $errors = [];
        $data = [
            'username' => '',
            'base_url' => $baseUrl,
        ];

        if ($request->method() === 'POST') {
            $username = (string) $request->getParsedBodyParam('username', '');
            $password = (string) $request->getParsedBodyParam('password', '');

            $this->validator->reset()
                ->required('username', $username)
                ->required('password', $password);

            if ($this->validator->fails()) {
                $errors = $this->validator->errors();
            } else {
                try {
                    $authResult = $this->authService->login($username, $password);

                    // authResult['user'] is expected to be an array (user->toArray()) from AuthService
                    $user = $authResult['user'] ?? null;
                    if (is_array($user)) {
                        $_SESSION['user_id'] = $user['id'] ?? null;
                        $_SESSION['username'] = $user['username'] ?? '';
                        $_SESSION['email'] = $user['email'] ?? '';
                    } else {
                        // Defensive: if object returned accidentally, cast to array if possible
                        if (is_object($user) && method_exists($user, 'toArray')) {
                            $u = $user->toArray();
                            $_SESSION['user_id'] = $u['id'] ?? null;
                            $_SESSION['username'] = $u['username'] ?? '';
                            $_SESSION['email'] = $u['email'] ?? '';
                        }
                    }

                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();

                    // Set refresh token cookie with improved settings
                    if (!empty($authResult['refresh_token'])) {
                        $cookieExpires = time() + $this->authService->getRefreshTTLSeconds();

                        setcookie(
                            'refresh_token',
                            $authResult['refresh_token'],
                            [
                                'expires' => $cookieExpires,
                                'path' => '/',
                                'domain' => '', // let PHP infer (suitable for localhost)
                                'secure' => false, // set to true in production with HTTPS
                                'httponly' => true,
                                'samesite' => 'Lax',
                            ]
                        );

                        // store as backup in session
                        $_SESSION['refresh_token'] = $authResult['refresh_token'];
                        $_SESSION['token_expires'] = $cookieExpires;
                    }

                    // Ensure session is saved
                    session_write_close();
                    session_start();

                    return $this->redirect($baseUrl ?: '/');

                } catch (\DomainException $e) {
                    $errors['login'] = $e->getMessage();
                }
            }

            $data['username'] = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->view('user/account', ['errors' => $errors, 'data' => $data]);
    }

    /**
     * Check if user is currently authenticated (AJAX endpoint)
     */
    public function checkAuth(Request $request): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAuthenticated = $this->isUserLoggedIn();

        $user = null;
        if ($isAuthenticated) {
            $user = [
                'id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'email' => $_SESSION['email'] ?? null,
            ];
        }

        return $this->json([
            'authenticated' => $isAuthenticated,
            'user' => $user,
        ]);
    }

    /**
     * Logout the current user
     */
    public function logout(Request $request): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Revoke refresh token if present
        $refreshToken = $_COOKIE['refresh_token'] ?? $_SESSION['refresh_token'] ?? null;
        if ($refreshToken) {
            try {
                $this->authService->revokeRefreshToken($refreshToken);
            } catch (\Throwable $e) {
                // ignore revoke failure
            }
        }

        // Clear refresh cookie
        if (isset($_COOKIE['refresh_token'])) {
            setcookie('refresh_token', '', time() - 3600, '/');
        }

        // Destroy session safely
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        $baseUrl = (string)($this->container->get('settings')['app']['base_path'] ?? '');
        return $this->redirect($baseUrl ?: '/');
    }

    /**
     * Check if user is logged in (helper method).
     * Tries session first, then refresh token rotation fallback.
     */
    private function isUserLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Session present
        if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
            return true;
        }

        // Try refresh token rotation
        $refreshToken = $_COOKIE['refresh_token'] ?? $_SESSION['refresh_token'] ?? null;
        if ($refreshToken) {
            try {
                $authResult = $this->authService->refreshToken($refreshToken);

                if (!empty($authResult) && isset($authResult['user'])) {
                    $user = $authResult['user'];
                    // user can be array or object; support both
                    if (is_array($user)) {
                        $_SESSION['user_id'] = $user['id'] ?? null;
                        $_SESSION['username'] = $user['username'] ?? '';
                        $_SESSION['email'] = $user['email'] ?? '';
                    } elseif (is_object($user) && method_exists($user, 'toArray')) {
                        $u = $user->toArray();
                        $_SESSION['user_id'] = $u['id'] ?? null;
                        $_SESSION['username'] = $u['username'] ?? '';
                        $_SESSION['email'] = $u['email'] ?? '';
                    }

                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    // update refresh token/cookie
                    if (!empty($authResult['refresh_token'])) {
                        $cookieExpires = time() + $this->authService->getRefreshTTLSeconds();
                        setcookie('refresh_token', $authResult['refresh_token'], [
                            'expires' => $cookieExpires,
                            'path' => '/',
                            'domain' => '',
                            'secure' => false,
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]);
                        $_SESSION['refresh_token'] = $authResult['refresh_token'];
                        $_SESSION['token_expires'] = $cookieExpires;
                    }
                    return true;
                }
            } catch (\Throwable $e) {
                // invalid token -> expire cookie & clear session
                unset($_SESSION['refresh_token']);
                setcookie('refresh_token', '', time() - 3600, '/');
            }
        }

        return false;
    }
}
