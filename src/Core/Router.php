<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use RuntimeException;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/**
 * Minimal router with middleware support.
 * 
 * Backward compatible: existing routes work unchanged.
 * New feature: optional third parameter for middleware.
 */
final class Router
{
    /** @var array<string, array<string, array{handler: callable|string, middleware: array<string>}>> */
    private array $routes = [];

    /**
     * Register GET route with optional middleware.
     *
     * @param string               $path
     * @param callable|string      $handler
     * @param array<string>        $middleware Optional middleware classes
     */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * Register POST route with optional middleware.
     *
     * @param string               $path
     * @param callable|string      $handler
     * @param array<string>        $middleware Optional middleware classes
     */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * Register a route for a specific HTTP method with optional middleware.
     *
     * @param string               $method  HTTP method (GET, POST, PUT, DELETE, etc)
     * @param string               $path
     * @param callable|string      $handler
     * @param array<string>        $middleware Optional middleware classes
     */
    public function add(string $method, string $path, $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $normalizedPath = $this->normalizePath($path);
        
        $this->routes[$method][$normalizedPath] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    /**
     * Dispatch the request to the matching handler.
     * Now runs middleware before the handler.
     */
    public function dispatch(string $method, string $uri, Container $container): ?Response
    {
        $method = strtoupper($method);
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');

        $request = $container->get(Request::class);

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $routePattern => $routeData) {
            $compiled = $this->compileRoute($routePattern);

            if (preg_match($compiled['regex'], $path, $matches) === 1) {
                $params = $this->extractNamedParams($matches);
                
                // Run middleware first
                foreach ($routeData['middleware'] as $middlewareClass) {
                    $middlewareResponse = $this->runMiddleware($middlewareClass, $request, $container);
                    
                    // If middleware returns a Response, short-circuit (e.g., redirect to login)
                    if ($middlewareResponse instanceof Response) {
                        return $middlewareResponse;
                    }
                }

                // All middleware passed, run the handler
                return $this->invokeHandler($routeData['handler'], $request, $params, $container);
            }
        }

        // No match: return 404
        $accept = $request->getHeader('accept', '');
        if (str_contains($accept, 'application/json')) {
            return Response::json(['error' => true, 'message' => 'Not Found'], 404);
        }

        return Response::html('<h1>404 Not Found</h1>', 404);
    }

    /**
     * Run a middleware class and return its response (if any).
     */
    private function runMiddleware(string $middlewareClass, Request $request, Container $container): ?Response
    {
        try {
            // Try to get middleware from container first
            $middleware = $container->get($middlewareClass);
        } catch (Throwable $e) {
            // If not in container, try to instantiate it
            if (!class_exists($middlewareClass)) {
                throw new RuntimeException("Middleware class {$middlewareClass} not found.", 0, $e);
            }

            // Use reflection to instantiate with dependencies
            $rc = new ReflectionClass($middlewareClass);
            $constructor = $rc->getConstructor();

            if ($constructor === null) {
                $middleware = $rc->newInstance();
            } else {
                $params = [];
                foreach ($constructor->getParameters() as $param) {
                    $ptype = $param->getType();
                    $resolved = null;

                    if ($ptype instanceof ReflectionNamedType && !$ptype->isBuiltin()) {
                        $depClass = $ptype->getName();

                        if ($depClass === Container::class) {
                            $resolved = $container;
                        } else {
                            try {
                                $resolved = $container->get($depClass);
                            } catch (Throwable $_) {
                                $resolved = null;
                            }
                        }
                    }

                    if ($resolved === null) {
                        if ($param->isDefaultValueAvailable()) {
                            $resolved = $param->getDefaultValue();
                        } else {
                            throw new RuntimeException(
                                "Unable to resolve parameter \${$param->getName()} for {$middlewareClass}::__construct()"
                            );
                        }
                    }

                    $params[] = $resolved;
                }

                $middleware = $rc->newInstanceArgs($params);
            }
        }

        // Call the middleware handle method
        if (!method_exists($middleware, 'handle')) {
            throw new RuntimeException("Middleware {$middlewareClass} must have a handle() method");
        }

        return $middleware->handle($request);
    }

    /**
     * Normalize path: ensure leading slash, trim trailing slash (except root).
     */
    private function normalizePath(string $path): string
    {
        $p = '/' . ltrim((string) $path, '/');
        if ($p !== '/' && str_ends_with($p, '/')) {
            $p = rtrim($p, '/');
        }
        return $p;
    }

    /**
     * Compile route pattern containing {named} params into regex.
     */
    private function compileRoute(string $route): array
    {
        if (strpos($route, '{') === false) {
            return [
                'regex' => '#^' . preg_quote($route, '#') . '$#',
                'paramNames' => [],
            ];
        }

        $paramNames = [];

        // Replace {name:regex} with named capture group using custom regex
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\}/',
            function ($m) use (&$paramNames) {
                $paramNames[] = $m[1];
                return '(?P<' . $m[1] . '>' . $m[2] . ')';
            },
            $route
        );

        // Replace {name} with named capture group using default pattern
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function ($m) use (&$paramNames) {
                $paramNames[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $pattern
        );

        $regex = '#^' . $pattern . '$#';
        return ['regex' => $regex, 'paramNames' => $paramNames];
    }

    /**
     * Extract named params from preg_match $matches.
     */
    private function extractNamedParams(array $matches): array
    {
        $params = [];

        foreach ($matches as $k => $v) {
            if (is_string($k)) {
                $params[$k] = is_scalar($v) ? (string) $v : '';
            }
        }

        return $params;
    }

    /**
     * Invoke the handler (callable or controller string).
     */
    private function invokeHandler(mixed $handler, Request $request, array $vars, Container $container): mixed
    {
        // Handler can be a callable already
        if (is_callable($handler)) {
            return $handler($request, $vars);
        }

        // Expect format "App\Controllers\SomeController@method"
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerClass, $method] = explode('@', $handler, 2);
        } else {
            throw new RuntimeException('Unsupported route handler: ' . (string) $handler);
        }

        // Try to get controller from container
        try {
            $controller = $container->get($controllerClass);
        } catch (Throwable $e) {
            // Fallback: instantiate with reflection
            if (!class_exists($controllerClass)) {
                throw new RuntimeException("Controller class {$controllerClass} not found.", 0, $e);
            }

            $rc = new ReflectionClass($controllerClass);
            $constructor = $rc->getConstructor();

            if ($constructor === null) {
                $controller = $rc->newInstance();
            } else {
                $params = [];
                foreach ($constructor->getParameters() as $param) {
                    $ptype = $param->getType();
                    $resolved = null;

                    if ($ptype instanceof ReflectionNamedType && !$ptype->isBuiltin()) {
                        $depClass = $ptype->getName();

                        if ($depClass === Container::class || is_subclass_of($depClass, Container::class)) {
                            $resolved = $container;
                        } else {
                            try {
                                $resolved = $container->get($depClass);
                            } catch (Throwable $_) {
                                $resolved = null;
                            }
                        }
                    }

                    if ($resolved === null) {
                        if ($param->isDefaultValueAvailable()) {
                            $resolved = $param->getDefaultValue();
                        } else {
                            throw new RuntimeException(
                                sprintf(
                                    'Unable to resolve parameter $%s for %s::__construct()',
                                    $param->getName(),
                                    $controllerClass
                                )
                            );
                        }
                    }

                    $params[] = $resolved;
                }

                $controller = $rc->newInstanceArgs($params);
            }
        }

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method {$method} not found on controller {$controllerClass}");
        }

        // Determine if the controller method expects Request as first parameter
        $rm = new \ReflectionMethod($controller, $method);
        $methodParams = $rm->getParameters();
        $expectsRequest = false;
        if (isset($methodParams[0])) {
            $firstType = $methodParams[0]->getType();
            if ($firstType instanceof ReflectionNamedType && !$firstType->isBuiltin()) {
                $firstTypeName = $firstType->getName();
                if ($firstTypeName === Request::class || is_subclass_of($firstTypeName, Request::class)) {
                    $expectsRequest = true;
                }
            }
        }

        // Cast route parameters based on method signature
        $methodArgs = $this->castRouteParameters($controller, $method, $vars);

        // Call method with or without Request depending on signature
        if ($expectsRequest) {
            return $controller->{$method}($request, ...$methodArgs);
        }

        return $controller->{$method}(...$methodArgs);
    }

    /**
     * Cast route parameters to match the method signature types.
     */
    private function castRouteParameters(object $controller, string $method, array $vars): array
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $parameters = $reflection->getParameters();

        // Detect whether the first parameter is Request and should be skipped
        $skipFirst = false;
        if (isset($parameters[0])) {
            $firstType = $parameters[0]->getType();
            if ($firstType instanceof ReflectionNamedType && !$firstType->isBuiltin()) {
                $firstTypeName = $firstType->getName();
                if ($firstTypeName === Request::class || is_subclass_of($firstTypeName, Request::class)) {
                    $skipFirst = true;
                }
            }
        }

        // Determine which parameters correspond to route parameters
        $routeParams = $skipFirst ? array_slice($parameters, 1) : $parameters;
        $castedArgs = [];
        $varValues = array_values($vars);

        foreach ($routeParams as $index => $param) {
            $value = $varValues[$index] ?? null;

            if ($value === null) {
                if ($param->isDefaultValueAvailable()) {
                    $castedArgs[] = $param->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        sprintf('Missing required route parameter $%s for %s::%s()',
                            $param->getName(),
                            get_class($controller),
                            $method
                        )
                    );
                }
                continue;
            }

            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->allowsNull()) {
                $castedArgs[] = match ($type->getName()) {
                    'int' => (int) $value,
                    'float' => (float) $value,
                    'bool' => (bool) $value,
                    'string' => (string) $value,
                    default => $value,
                };
            } else {
                $castedArgs[] = $value;
            }
        }

        return $castedArgs;
    }
}