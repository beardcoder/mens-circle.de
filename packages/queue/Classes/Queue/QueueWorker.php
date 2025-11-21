<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

use Psr\Log\LoggerInterface;

class QueueWorker
{
    public function __construct(
        private readonly QueueRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function work(
        string $queue = 'default',
        int $maxJobs = 0,
        int $sleepSeconds = 5,
        int $retryAfterSeconds = 90,
        int $backoffSeconds = 30,
    ): int {
        $processed = 0;

        while ($maxJobs === 0 || $processed < $maxJobs) {
            $didWork = $this->runOnce($queue, $retryAfterSeconds, $backoffSeconds);

            if ($didWork) {
                ++$processed;
                continue;
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            } else {
                break;
            }
        }

        return $processed;
    }

    public function runOnce(
        string $queue = 'default',
        int $retryAfterSeconds = 90,
        int $backoffSeconds = 30,
    ): bool {
        $job = $this->repository->reserveNext($queue, $retryAfterSeconds);
        if (!$job instanceof QueueJob) {
            return false;
        }

        try {
            $job->job->handle();

            $this->repository->markCompleted($job->uid);
            $this->logger->info('Queue job finished and removed from queue', [
                'job' => $job->job::class,
                'queue' => $job->queue,
                'uid' => $job->uid,
                'attempt' => $job->attempts,
            ]);

            return true;
        } catch (\Throwable $exception) {
            $this->handleFailure($job, $exception, $backoffSeconds);

            return false;
        }
    }

    private function handleFailure(QueueJob $job, \Throwable $exception, int $backoffSeconds): void
    {
        if ($job->isFinalAttempt()) {
            $this->repository->markFailed($job->uid, $exception->getMessage());
            $this->logger->error('Queue job failed permanently and remains stored with error details', [
                'job' => $job->job::class,
                'queue' => $job->queue,
                'uid' => $job->uid,
                'attempts' => $job->attempts,
                'max_attempts' => $job->maxAttempts,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $this->repository->release($job->uid, $backoffSeconds);

        $this->logger->warning('Queue job failed, scheduled retry', [
            'job' => $job->job::class,
            'queue' => $job->queue,
            'uid' => $job->uid,
            'attempt' => $job->attempts,
            'max_attempts' => $job->maxAttempts,
            'error' => $exception->getMessage(),
        ]);
    }
}
