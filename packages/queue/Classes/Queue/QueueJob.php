<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

readonly class QueueJob
{
    public function __construct(
        public int $uid,
        public string $queue,
        public JobInterface $job,
        public int $availableAt,
        public ?int $reservedAt,
        public int $attempts,
        public int $maxAttempts,
        public ?string $lastError,
    ) {
    }

    public function isFinalAttempt(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }
}
