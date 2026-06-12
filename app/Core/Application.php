<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

class Application
{
    private static ?Application $instance = null;
    private array $bindings = [];
    private array $singletons = [];
    private array $instances = [];
    private string $basePath;

    private function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->instances[static::class] = $this;
        $this->instances[self::class] = $this;
    }

    public static function getInstance(string $basePath = ''): static
    {
        if (static::$instance === null) {
            if (empty($basePath)) {
                throw new RuntimeException('Application base path required for first initialization.');
            }
            static::$instance = new static($basePath);
        }
        return static::$instance;
    }

    public function bind(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $instance = $this->build($this->singletons[$abstract]);
            $this->instances[$abstract] = $instance;
            return $instance;
        }

        if (isset($this->bindings[$abstract])) {
            return $this->build($this->bindings[$abstract]);
        }

        return $this->resolve($abstract);
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || isset($this->singletons[$abstract])
            || isset($this->bindings[$abstract]);
    }

    private function build(Closure|string $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        return $this->resolve($concrete);
    }

    private function resolve(string $class): mixed
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new RuntimeException("Cannot resolve [{$class}]: " . $e->getMessage());
        }

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Cannot resolve dependency [{$parameter->getName()}] of [{$class}]."
                );
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources') . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path ? '/' . ltrim($path, '/') : '');
    }
}
