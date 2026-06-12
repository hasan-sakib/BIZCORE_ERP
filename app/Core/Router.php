<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use App\Foundation\Application;
use App\Http\Request;
use App\Http\Response;

class Router
{
    /** Maps short middleware aliases → fully-qualified class names. */
    private const MIDDLEWARE_MAP = [
        'auth'       => \App\Core\Middleware\AuthMiddleware::class,
        'guest'      => \App\Core\Middleware\GuestMiddleware::class,
        'csrf'       => \App\Core\Middleware\CsrfMiddleware::class,
        'mfa'        => \App\Core\Middleware\MfaMiddleware::class,
        'verified'   => \App\Core\Middleware\VerifiedMiddleware::class,
        'throttle'   => \App\Core\Middleware\RateLimitMiddleware::class,
        'permission' => \App\Core\Middleware\PermissionMiddleware::class,
        'module'     => \App\Core\Middleware\ModuleMiddleware::class,
        'signed'     => \App\Core\Middleware\SignedMiddleware::class,
    ];

    private array $routes = [];
    private array $namedRoutes = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, array|string $handler, ?string $name = null, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware, $name);
    }

    public function post(string $path, array|string $handler, ?string $name = null, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware, $name);
    }

    public function put(string $path, array|string $handler, ?string $name = null, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware, $name);
    }

    public function patch(string $path, array|string $handler, ?string $name = null, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware, $name);
    }

    public function delete(string $path, array|string $handler, ?string $name = null, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware, $name);
    }

    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= $attributes['prefix'] ?? '';
        $this->groupMiddleware = array_merge(
            $this->groupMiddleware,
            $attributes['middleware'] ?? []
        );

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, array|string $handler, array $middleware, ?string $name): void
    {
        $fullPath = rtrim($this->groupPrefix . $path, '/') ?: '/';
        $allMiddleware = array_merge($this->groupMiddleware, $middleware);
        $pattern = $this->compilePattern($fullPath);

        $route = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $allMiddleware,
            'name' => $name,
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $fullPath;
        }
    }

    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        $pattern = preg_replace('/\{([a-zA-Z_]+):([^}]+)\}/', '($2)', $pattern);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): Response
    {
        $method = strtoupper($request->method());
        $uri    = '/' . trim($request->path(), '/');

        $methodMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                $methodMatched = true;
                continue;
            }

            array_shift($matches);

            $result = $this->runMiddlewarePipeline($route['middleware'], $route['handler'], $matches, $request);

            return $result instanceof Response ? $result : Response::make((string)($result ?? ''));
        }

        if ($methodMatched) {
            return Response::make(
                json_encode(['error' => 'Method Not Allowed']) ?: '',
                405
            )->withHeader('Allow', 'GET, POST, PUT, PATCH, DELETE')
             ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        throw new NotFoundException("Route not found: {$method} {$uri}");
    }

    private function runMiddlewarePipeline(array $middleware, array|string $handler, array $params, Request $request): mixed
    {
        $app = Application::getInstance();

        $pipeline = array_reverse($middleware);
        $next = function () use ($handler, $params, $request, $app) {
            return $this->callHandler($handler, $params, $request, $app);
        };

        foreach ($pipeline as $middlewareEntry) {
            // Resolve "alias:param1,param2" → class name + parameter string.
            $parts      = explode(':', (string)$middlewareEntry, 2);
            $alias      = $parts[0];
            $mwParams   = isset($parts[1]) ? explode(',', $parts[1]) : [];
            $mwClass    = self::MIDDLEWARE_MAP[$alias] ?? (class_exists($alias) ? $alias : null);

            if ($mwClass === null) {
                continue; // Unknown middleware — skip rather than crash.
            }

            $mw        = $app->get($mwClass);
            $nextInner = $next;
            $next      = function () use ($mw, $request, $mwParams, $nextInner) {
                return $mwParams !== []
                    ? $mw->handle($request, $nextInner, ...$mwParams)
                    : $mw->handle($request, $nextInner);
            };
        }

        return $next();
    }

    private function callHandler(array|string $handler, array $params, Request $request, Application $app): mixed
    {
        if (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
        } else {
            [$class, $method] = $handler;
        }

        // Resolve short controller names to full qualified class names.
        if (!str_contains($class, '\\') || !class_exists($class)) {
            $fqcn = 'App\\Controllers\\' . $class;
            if (class_exists($fqcn)) {
                $class = $fqcn;
            }
        }

        $controller = $app->get($class);

        // Inject $request for Request-typed params; fill remaining params with
        // route segments in order. Avoids TypeError on mixed signatures.
        $reflMethod  = new \ReflectionMethod($controller, $method);
        $routeValues = array_values($params);
        $routeIdx    = 0;
        $args        = [];

        foreach ($reflMethod->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
            } elseif (isset($routeValues[$routeIdx])) {
                $raw = $routeValues[$routeIdx++];
                if ($type instanceof \ReflectionNamedType) {
                    $raw = match ($type->getName()) {
                        'int'   => (int) $raw,
                        'float' => (float) $raw,
                        'bool'  => (bool) $raw,
                        default => $raw,
                    };
                }
                $args[] = $raw;
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $controller->$method(...$args);
    }

    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $value) {
            $path = preg_replace('/\{' . $key . '\}/', (string) $value, $path);
        }
        return $path;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
