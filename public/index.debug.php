<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// public/index project bootstrap

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_ROOT', dirname(__DIR__));

echo "APP_ROOT: " . APP_ROOT . "\n<br>";

// Autoload via composer
$autoloadFile = APP_ROOT . '/vendor/autoload.php';
echo "Autoload file: " . $autoloadFile . " (exists: " . (file_exists($autoloadFile) ? 'YES' : 'NO') . ")\n<br>";

if (!file_exists($autoloadFile)) {
    die("Composer autoload file not found. Run 'composer install' first.");
}

require_once $autoloadFile;

// Load settings (must return array)
$settingsFile = APP_ROOT . '/app/Config/settings.php';
echo "Settings file: " . $settingsFile . " (exists: " . (file_exists($settingsFile) ? 'YES' : 'NO') . ")\n<br>";

if (!is_file($settingsFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'message' => 'Missing settings.php']);
    exit;
}

$settings = (array) require $settingsFile;
echo "Settings loaded successfully\n<br>";

/**
 * Instantiate the container
 */
try {
    $container = new App\Core\Container();
    echo "Container created successfully\n<br>";
} catch (\Throwable $e) {
    die("Failed to create container: " . $e->getMessage());
}

/**
 * Store settings in the container for services that need them.
 */
$container->set('settings', function () use ($settings) {
    return $settings;
});

/**
 * Core service factories 
 */
$container->set(App\Core\Request::class, function () {
    $method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri     = $_SERVER['REQUEST_URI'] ?? '/';
    $query   = $_GET ?? [];
    $body    = $_POST ?? [];
    $server  = $_SERVER ?? [];

    // getallheaders() may not exist under some SAPIs — provide a safe fallback
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    return new App\Core\Request($method, $uri, $query, $body, $server, $headers);
});

$container->set(App\Core\Response::class, function () {
    return new App\Core\Response();
});

/**
 * Router expects the Container in its constructor.
 */
$container->set(App\Core\Router::class, function ($c) {
    return new App\Core\Router();
});

/**
 * Provide a 'view' service (controllers rely on this).
 */
$container->set('view', function ($c) {
    $settings = $c->get('settings') ?? [];
    $viewsPath = $settings['views_path'] ?? (APP_ROOT . '/app/Views');

    if (class_exists(\App\Core\View::class)) {
        return new \App\Core\View($viewsPath);
    }

    // Lightweight fallback
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

echo "Core services registered successfully\n<br>";

/**
 * Optionally load additional services from app/Config/services.php
 */
$servicesFile = APP_ROOT . '/app/Config/services.php';
echo "Services file: " . $servicesFile . " (exists: " . (file_exists($servicesFile) ? 'YES' : 'NO') . ")\n<br>";

if (is_file($servicesFile)) {
    try {
        $register = require $servicesFile;
        if (is_callable($register)) {
            $register($container);
            echo "Additional services registered successfully\n<br>";
        } else {
            echo "Services file did not return a callable\n<br>";
        }
    } catch (\Throwable $e) {
        echo "Error loading services: " . $e->getMessage() . "\n<br>";
        throw $e;
    }
}

/**
 * Helper to load route files that RETURN a callable: function(Router $router): void { ... }
 */
$loadRoutes = static function (string $path, App\Core\Router $router): void {
    if (!is_file($path)) {
        echo "Route file not found: {$path}\n<br>";
        return;
    }
    echo "Loading routes from: {$path}\n<br>";
    $routes = require $path;
    if (is_callable($routes)) {
        $routes($router);
        echo "Routes loaded successfully\n<br>";
    } else {
        echo "Route file did not return a callable\n<br>";
    }
};

// Load routes
$webRoutes = APP_ROOT . '/src/routes/web.php';
echo "Web routes file: " . $webRoutes . " (exists: " . (file_exists($webRoutes) ? 'YES' : 'NO') . ")\n<br>";

try {
    $router = $container->get(App\Core\Router::class);
    $loadRoutes($webRoutes, $router);
} catch (\Throwable $e) {
    echo "Error creating router or loading routes: " . $e->getMessage() . "\n<br>";
    throw $e;
}

/**
 * Dispatch
 */
try {
    $request = $container->get(App\Core\Request::class);
    echo "Request created: " . $request->method() . " " . $request->uri() . "\n<br>";
    
    // Keep your router signature: dispatch($method, $uri, $container)
    $router->dispatch($request->method(), $request->uri(), $container);

} catch (\Throwable $e) {
    echo "Error during dispatch: " . $e->getMessage() . "\n<br>";
    echo "Exception class: " . get_class($e) . "\n<br>";
    echo "File: " . $e->getFile() . "\n<br>";
    echo "Line: " . $e->getLine() . "\n<br>";
    echo "Stack trace:\n<pre>" . $e->getTraceAsString() . "</pre>\n<br>";
    
    $debug = $settings['app']['debug'] ?? false;
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