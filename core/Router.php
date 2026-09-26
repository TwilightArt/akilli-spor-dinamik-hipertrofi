<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight MVC Router with Parameter Matching & Middleware Support
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
        return $this;
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
        return $this;
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        // Convert route pattern with parameters like /workout/live/{id} to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(string $uri, string $requestMethod): void
    {
        // Remove query strings
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $method = strtoupper($requestMethod);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Execute Middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $this->executeMiddleware($middleware);
                }

                // Extract named parameter arguments as positional values for robust PHP 8 dispatching
                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

                // Call Handler
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $params);
                    return;
                }

                if (is_array($route['handler'])) {
                    [$controllerClass, $action] = $route['handler'];
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $action)) {
                            call_user_func_array([$controller, $action], $params);
                            return;
                        }
                    }
                }

                throw new \RuntimeException("Handler not found for route: {$path}");
            }
        }

        // 404 Not Found fallback
        http_response_code(404);
        if (str_starts_with($path, '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Endpoint bulunamadı (404)'], JSON_UNESCAPED_UNICODE);
        } else {
            echo "<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'><title>404 - Sayfa Bulunamadı</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-900 text-white min-h-screen flex items-center justify-center p-4'><div class='text-center max-w-md'><h1 class='text-7xl font-bold text-indigo-500 mb-4'>404</h1><h2 class='text-2xl font-semibold mb-2'>Sayfa Bulunamadı</h2><p class='text-slate-400 mb-6'>Aradığınız sayfa silinmiş veya adresi değiştirilmiş olabilir.</p><a href='/' class='inline-block bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-6 py-3 rounded-xl shadow-lg transition-all'>Ana Sayfaya Dön</a></div></body></html>";
        }
    }

    private function executeMiddleware(string $middleware): void
    {
        switch ($middleware) {
            case 'auth':
                Security::startSession();
                if (empty($_SESSION['user'])) {
                    $_SESSION['flash'] = [
                        'type'    => 'warning',
                        'message' => 'Lütfen devam etmek için giriş yapınız.'
                    ];
                    header('Location: /login');
                    exit;
                }
                break;

            case 'guest':
                Security::startSession();
                if (!empty($_SESSION['user'])) {
                    header('Location: /workout/dashboard');
                    exit;
                }
                break;

            case 'csrf':
                Security::verifyRequestCsrf();
                break;
        }
    }
}
