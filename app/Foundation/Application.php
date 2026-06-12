<?php

declare(strict_types=1);

namespace App\Foundation;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * BizCore Application Container
 *
 * Lightweight IoC / service container with:
 *  - Singleton (shared) binding
 *  - Factory (transient) binding
 *  - Contextual alias resolution
 *  - Service provider boot lifecycle
 *  - Auto-wiring for simple constructor injection (reflection-based)
 */
final class Application implements ContainerInterface
{
    // ── State ────────────────────────────────────────────────────────────────

    /** @var Application|null Singleton application instance */
    private static ?Application $instance = null;

    /** @var array<string, callable> Factory closures */
    private array $bindings = [];

    /** @var array<string, mixed> Resolved singleton instances */
    private array $instances = [];

    /** @var array<string, string> Abstract → concrete alias map */
    private array $aliases = [];

    /** @var list<\App\Providers\ServiceProvider> Registered providers */
    private array $providers = [];

    /** @var bool Whether providers have been booted */
    private bool $booted = false;

    /** @var array<string, mixed> Loaded config files cache */
    private array $configCache = [];

    // ── Constructor ──────────────────────────────────────────────────────────

    private function __construct(private readonly string $basePath) {}

    // ── Singleton accessor ───────────────────────────────────────────────────

    public static function getInstance(string $basePath = ''): self
    {
        if (self::$instance === null) {
            if ($basePath === '') {
                throw new \LogicException(
                    'Application::getInstance() called before Application was created. ' .
                    'Provide basePath on first call.'
                );
            }
            self::$instance = new self($basePath);
        }

        return self::$instance;
    }

    /** Destroy the singleton (testing only). */
    public static function flush(): void
    {
        self::$instance = null;
    }

    // ── PSR-11 ContainerInterface ────────────────────────────────────────────

    /**
     * @template T of object
     * @param  class-string<T>|string $id
     * @return T|mixed
     */
    public function get(string $id): mixed
    {
        $id = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            return ($this->bindings[$id])($this);
        }

        if (class_exists($id)) {
            return $this->resolve($id);
        }

        throw new class("No binding registered for [{$id}].")
            extends \RuntimeException
            implements NotFoundExceptionInterface {};
    }

    public function has(string $id): bool
    {
        $id = $this->aliases[$id] ?? $id;

        return isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    // ── Binding API ──────────────────────────────────────────────────────────

    public function bind(string $abstract, callable $factory): void
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function (Application $app) use ($abstract, $factory): mixed {
            if (! isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($app);
            }
            return $this->instances[$abstract];
        });
    }

    public function instance(string $abstract, mixed $concrete): void
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;
        $this->instances[$abstract] = $concrete;
    }

    public function alias(string $abstract, string $concrete): void
    {
        $this->aliases[$abstract] = $concrete;
    }

    // ── Resolution Helpers ───────────────────────────────────────────────────

    /**
     * @template T of object
     * @param  class-string<T> $concrete
     * @return T
     */
    private function resolve(string $concrete): object
    {
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new class("Target class [{$concrete}] does not exist.", 0, $e)
                extends \RuntimeException
                implements ContainerExceptionInterface {};
        }

        if (! $reflector->isInstantiable()) {
            throw new class("Target [{$concrete}] is not instantiable.")
                extends \RuntimeException
                implements ContainerExceptionInterface {};
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = array_map(
            function (\ReflectionParameter $param) use ($concrete): mixed {
                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                    return $this->get($type->getName());
                }

                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }

                throw new class(
                    "Cannot resolve parameter \${$param->getName()} for [{$concrete}]."
                )
                    extends \RuntimeException
                    implements ContainerExceptionInterface {};
            },
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    // ── Service Provider Lifecycle ───────────────────────────────────────────

    public function register(\App\Providers\ServiceProvider $provider): void
    {
        $provider->register($this);
        $this->providers[] = $provider;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot($this);
            }
        }

        $this->booted = true;
    }

    // ── Configuration helpers ────────────────────────────────────────────────

    public function config(string $key, mixed $default = null): mixed
    {
        [$file, $rest] = array_pad(explode('.', $key, 2), 2, null);

        if (! isset($this->configCache[$file])) {
            $path = $this->basePath . '/config/' . $file . '.php';
            if (! file_exists($path)) {
                return $default;
            }
            $this->configCache[$file] = require $path;
        }

        if ($rest === null) {
            return $this->configCache[$file] ?? $default;
        }

        return $this->dotGet($this->configCache[$file], $rest, $default);
    }

    private function dotGet(array $array, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    // ── Path Helpers ─────────────────────────────────────────────────────────

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    public function routesPath(string $path = ''): string
    {
        return $this->basePath('routes' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    // ── Environment Helpers ──────────────────────────────────────────────────

    public function environment(): string
    {
        return $_ENV['APP_ENV'] ?? 'local';
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function isDebug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
