<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

class QueueDispatcher
{
    public function __construct(
        private readonly QueueRepository $repository,
    ) {
    }

    public function dispatch(
        JobInterface $job,
        string $queue = 'default',
        int $delaySeconds = 0,
        int $maxAttempts = 1,
        int $priority = 0,
    ): int {
        return $this->repository->enqueue(
            job: $job,
            queue: $queue,
            delaySeconds: $delaySeconds,
            maxAttempts: $maxAttempts,
            priority: $priority,
        );
    }
}
