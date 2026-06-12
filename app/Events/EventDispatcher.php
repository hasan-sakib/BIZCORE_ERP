<?php

declare(strict_types=1);

namespace App\Events;

use App\Foundation\Application;

class EventDispatcher
{
    /** @var array<string, list<callable>> */
    private array $listeners = [];

    public function __construct(private readonly Application $app) {}

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }

    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }
}
