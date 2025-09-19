<?php
declare(strict_types=1);

// public/index.php - application front controller / bootstrap

// --- minimal error reporting in debug mode (will be toggled after settings loaded) ---
ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// --- Session configuration (must be set before session_start) ---
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
       || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

// For localhost, don't set a domain (blank will work). For other hosts, use the host.
$domain = ($host === 'localhost' || $host === '127.0.0.1') ? '' : $host;

// PHP >= 7.3 supports array param
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $domain,
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- APP_ROOT ---
define('APP_ROOT', dirname(__DIR__));

// Autoload via composer
$composer = APP_ROOT . '/vendor/autoload.php';
if (!is_file($composer)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'message' => 'Composer autoload not found. Run composer install.']);
    exit;
}
require_once $composer;

// Load settings (must return array)
$settingsFile = APP_ROOT . '/app/Config/settings.php';
if (!is_file($settingsFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'message' => 'Missing settings.php']);
    exit;
}
$settings = (array) require $settingsFile;

// Toggle display_errors if debug is enabled in settings
$debug = (bool) ($settings['app']['debug'] ?? false);
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

// --- Container ---
$container = new App\Core\Container();
// expose for legacy view helpers that look for $GLOBALS['container']
$GLOBALS['container'] = $container;

// Store settings in the container
$container->set('settings', function () use ($settings) {
    return $settings;
});

// Core services
$container->set(App\Core\Request::class, function () {
    $method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri     = $_SERVER['REQUEST_URI'] ?? '/';
    $query   = $_GET ?? [];
    $body    = $_POST ?? [];
    $server  = $_SERVER ?? [];
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    return new App\Core\Request($method, $uri, $query, $body, $server, $headers);
});

$container->set(App\Core\Response::class, function () {
    return new App\Core\Response();
});

// Router (no heavy wiring here; Router resolves controllers via container)
$container->set(App\Core\Router::class, function ($c) {
    return new App\Core\Router();
});

// View service
$container->set('view', function ($c) {
    $settings = $c->get('settings') ?? [];
    $viewsPath = $settings['views_path'] ?? (APP_ROOT . '/app/Views');

    if (class_exists(\App\Core\View::class)) {
        return new \App\Core\View($viewsPath);
    }

    // Lightweight fallback renderer (keeps existing templates working)
    return new class ($viewsPath) {
        private string $path;
        public function __construct(string $p) { $this->path = rtrim($p, DIRECTORY_SEPARATOR); }
        public function render(string $template, array $params = []): string {
            $file = $this->path . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($template, '/\\')) . '.php';
            if (!is_file($file)) {
                throw new \RuntimeException("View not found: {$file}");
            }
            extract($params, EXTR_SKIP);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        }
    };
});

// Optionally load app services (app/Config/services.php)
$servicesFile = APP_ROOT . '/app/Config/services.php';
if (is_file($servicesFile)) {
    $register = require $servicesFile;
    if (is_callable($register)) {
        $register($container);
    } else {
        if ($debug) {
            error_log('[BOOT] services.php did not return a callable.');
        }
    }
}

// Helper to load route file that returns callable: function(Router $router): void { ... }
$loadRoutes = static function (string $path, App\Core\Router $router) use ($debug): void {
    if (!is_file($path)) {
        if ($debug) {
            error_log("[BOOT] routes file not found: {$path}");
        }
        return;
    }
    $routes = require $path;
    if (is_callable($routes)) {
        $routes($router);
    } else {
        if ($debug) {
            error_log("[BOOT] routes file {$path} did not return a callable.");
        }
    }
};

// Load routes from project routes directory
$webRoutes = APP_ROOT . '/routes/web.php';
$loadRoutes($webRoutes, $container->get(App\Core\Router::class));

// Dispatch
$request = $container->get(App\Core\Request::class);
$router  = $container->get(App\Core\Router::class);

try {
    $response = $router->dispatch($request->method(), $request->uri(), $container);

    if ($response instanceof App\Core\Response) {
        $response->send();
    } else {
        // Nothing returned — assume handler already emitted output
    }

} catch (\Throwable $e) {
    $debug = (bool) ($settings['app']['debug'] ?? false);
    header('Content-Type: application/json; charset=utf-8', true, 500);

    if ($debug) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'exception' => [
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        if (method_exists(App\Core\Response::class, 'error')) {
            App\Core\Response::error(500, 'Internal Server Error');
        } else {
            echo json_encode(['error' => true, 'message' => 'Internal Server Error']);
        }
    }
    exit;
}
