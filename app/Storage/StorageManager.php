<?php

declare(strict_types=1);

namespace App\Storage;

use Psr\Log\LoggerInterface;

class StorageManager
{
    public function __construct(
        private readonly string          $basePath,
        private readonly string          $driver  = 'local',
        private readonly ?LoggerInterface $logger  = null,
    ) {}

    public function put(string $path, string $contents): bool
    {
        $full = $this->fullPath($path);
        $dir  = dirname($full);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($full, $contents) !== false;
    }

    public function get(string $path): ?string
    {
        $full = $this->fullPath($path);
        return file_exists($full) ? (file_get_contents($full) ?: null) : null;
    }

    public function delete(string $path): bool
    {
        $full = $this->fullPath($path);
        return !file_exists($full) || unlink($full);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->fullPath($path));
    }

    public function url(string $path): string
    {
        return '/storage/' . ltrim($path, '/');
    }

    public function path(string $path): string
    {
        return $this->fullPath($path);
    }

    private function fullPath(string $path): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }
}
