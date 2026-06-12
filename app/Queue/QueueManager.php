<?php

declare(strict_types=1);

namespace App\Queue;

use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class QueueManager
{
    public function __construct(
        private readonly RedisClient      $redis,
        private readonly LoggerInterface  $logger,
        private readonly array            $config = [],
    ) {}

    public function push(string $job, array $data = [], string $queue = 'default'): bool
    {
        $payload = json_encode([
            'job'       => $job,
            'data'      => $data,
            'timestamp' => time(),
        ]);

        $this->redis->lpush("bizcore:queue:{$queue}", [$payload]);
        $this->logger->debug("Job [{$job}] pushed to queue [{$queue}]");
        return true;
    }

    public function pop(string $queue = 'default'): ?array
    {
        $payload = $this->redis->rpop("bizcore:queue:{$queue}");
        return $payload ? (array) json_decode($payload, true) : null;
    }

    public function size(string $queue = 'default'): int
    {
        return (int) $this->redis->llen("bizcore:queue:{$queue}");
    }
}
