<?php
// debug_checkout.php - Place this in your /public folder
declare(strict_types=1);

// Enable error reporting
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>Checkout Debug Test</h1>";
echo "<pre>";

try {
    // Bootstrap the application (same as index.php)
    define('APP_ROOT', dirname(__DIR__));
    
    $composer = APP_ROOT . '/vendor/autoload.php';
    if (!is_file($composer)) {
        throw new Exception('Composer autoload not found');
    }
    require_once $composer;
    
    $settingsFile = APP_ROOT . '/app/Config/settings.php';
    if (!is_file($settingsFile)) {
        throw new Exception('Missing settings.php');
    }
    $settings = (array) require $settingsFile;
    
    echo "✓ Bootstrap successful\n";
    
    // Create container
    $container = new App\Core\Container();
    $container->set('settings', function () use ($settings) {
        return $settings;
    });
    
    // Core services (copied from index.php)
    $container->set(App\Core\Request::class, function () {
        $method  = $_SERVER['REQUEST_METHOD'] ?? 'POST';
        $uri     = $_SERVER['REQUEST_URI'] ?? '/checkout/create';
        $query   = $_GET ?? [];
        $body    = $_POST ?? [];
        $server  = $_SERVER ?? [];
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        return new App\Core\Request($method, $uri, $query, $body, $server, $headers);
    });

    $container->set(App\Core\Response::class, function () {
        return new App\Core\Response();
    });

    // View service (copied from index.php)
    $container->set('view', function ($c) {
        $settings = $c->get('settings') ?? [];
        $viewsPath = $settings['views_path'] ?? (APP_ROOT . '/app/Views');

        if (class_exists(\App\Core\View::class)) {
            return new \App\Core\View($viewsPath);
        }

        // Lightweight fallback renderer
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
    
    // Load services
    $servicesFile = APP_ROOT . '/app/Config/services.php';
    if (is_file($servicesFile)) {
        $register = require $servicesFile;
        if (is_callable($register)) {
            $register($container);
        }
    }
    
    echo "✓ Container and services loaded\n";
    
    // Start session and simulate login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Simulate a logged-in user
    $_SESSION['user_id'] = 999; // Test user ID
    $_SESSION['username'] = 'test_user';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    echo "✓ Session started with test user ID: 999\n";
    
    // Try to get the CheckoutController
    $controller = new App\Controllers\CheckoutController($container);
    echo "✓ CheckoutController created\n";
    
    // Create a fake request
    $request = new App\Core\Request('POST', '/checkout/create', [], [], $_SERVER, []);
    
    echo "✓ Request object created\n";
    echo "Now testing checkout creation...\n";
    echo "==========================================\n";
    
    // Call the create method
    $response = $controller->create($request);
    
    echo "==========================================\n";
    echo "Response received!\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if (method_exists($response, 'getStatusCode')) {
        echo "Status Code: " . $response->getStatusCode() . "\n";
    }
    
    if (method_exists($response, 'getBody')) {
        $body = $response->getBody();
        echo "Response Body: " . $body . "\n";
        
        $decoded = json_decode($body, true);
        echo "JSON Decoded: " . ($decoded === null ? 'NULL' : print_r($decoded, true)) . "\n";
    }
    
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>