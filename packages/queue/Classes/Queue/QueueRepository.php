<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class QueueRepository
{
    private const TABLE = 'tx_queue_job';

    private Connection $connection;

    public function __construct(
        ConnectionPool $connectionPool,
        private readonly JobSerializer $serializer,
    ) {
        $this->connection = $connectionPool->getConnectionForTable(self::TABLE);
    }

    public function enqueue(JobInterface $job, string $queue, int $delaySeconds, int $maxAttempts, int $priority = 0): int
    {
        $now = time();

        $this->connection->insert(self::TABLE, [
            'queue' => $queue,
            'payload' => $this->serializer->encode($job),
            'available_at' => $now + max(0, $delaySeconds),
            'reserved_at' => 0,
            'attempts' => 0,
            'max_attempts' => max(1, $maxAttempts),
            'priority' => $priority,
            'crdate' => $now,
            'tstamp' => $now,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function reserveNext(string $queue, int $retryAfterSeconds): ?QueueJob
    {
        $now = time();
        $expiredAt = $now - max(0, $retryAfterSeconds);

        $this->connection->beginTransaction();

        try {
            $row = $this->connection->executeQuery(
                'SELECT * FROM '.self::TABLE.' WHERE queue = :queue
                    AND available_at <= :now
                    AND attempts < max_attempts
                    AND (
                        reserved_at = 0
                        OR reserved_at <= :expired
                    )
                    ORDER BY priority DESC, available_at ASC, uid ASC
                    LIMIT 1
                    FOR UPDATE',
                [
                    'queue' => $queue,
                    'now' => $now,
                    'expired' => $expiredAt,
                ],
            )->fetchAssociative();

            if ($row === false) {
                $this->connection->commit();

                return null;
            }

            $attempts = (int) $row['attempts'] + 1;

            $this->connection->update(self::TABLE, [
                'reserved_at' => $now,
                'tstamp' => $now,
                'attempts' => $attempts,
            ], [
                'uid' => (int) $row['uid'],
            ]);

            $this->connection->commit();

            return $this->hydrateJob($row, $attempts, $now);
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function markCompleted(int $uid): void
    {
        $this->connection->delete(self::TABLE, ['uid' => $uid]);
    }

    public function markFailed(int $uid, string $errorMessage): void
    {
        $now = time();

        $this->connection->update(self::TABLE, [
            'tstamp' => $now,
            'last_error' => substr($errorMessage, 0, 10000),
            'reserved_at' => 0,
        ], [
            'uid' => $uid,
        ]);
    }

    public function release(int $uid, int $delaySeconds): void
    {
        $now = time();
        $releaseAt = $now + max(0, $delaySeconds);

        $this->connection->update(self::TABLE, [
            'available_at' => $releaseAt,
            'reserved_at' => 0,
            'tstamp' => $now,
        ], [
            'uid' => $uid,
        ]);
    }

    private function hydrateJob(array $row, int $attempts, int $reservedAt): QueueJob
    {
        if (!isset($row['payload'])) {
            throw new \InvalidArgumentException('Queue row missing payload.');
        }

        $job = $this->serializer->decode((string) $row['payload']);

        return new QueueJob(
            uid: (int) $row['uid'],
            queue: (string) $row['queue'],
            job: $job,
            availableAt: (int) $row['available_at'],
            reservedAt: $reservedAt > 0 ? $reservedAt : null,
            attempts: $attempts,
            maxAttempts: (int) $row['max_attempts'],
            lastError: isset($row['last_error']) && $row['last_error'] !== '' ? (string) $row['last_error'] : null,
        );
    }
}
