# Beardcoder Queue (mc_queue)

Simple, database-backed queue for TYPO3 v13 inspired by the Laravel queue API. Jobs are stored in `tx_queue_job`.

## Installation

1. Add the package to `composer.json` (`beardcoder/queue`).
2. Run `composer install` (or `composer update beardcoder/queue`) and `typo3 extension:setup` to create the table `tx_queue_job`.
3. Activate the extension in the TYPO3 Extension Manager if required.

## Writing jobs

Implement `Beardcoder\Queue\Queue\JobInterface` and put your logic into `handle()`. Keep only serializable data (IDs, strings, DTOs) as properties, because the job instance is serialized into the database. Fetch services inside `handle()`.

```php
<?php

declare(strict_types=1);

use Beardcoder\Queue\Queue\JobInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class SendWelcomeMailJob implements JobInterface
{
    public function __construct(
        private int $userId,
        private string $email,
    ) {
    }

    public function handle(): void
    {
        $mailer = GeneralUtility::makeInstance(Mailer::class);
        $mailer->sendWelcomeMail($this->userId, $this->email);
    }
}
```

## Dispatching jobs

```php
use Beardcoder\Queue\Queue\QueueDispatcher;

// e.g. in a controller or service
$jobId = $this->queueDispatcher->dispatch(
    job: new SendWelcomeMailJob($userId, $email),
    queue: 'emails',
    delaySeconds: 60,     // optional delay
    maxAttempts: 3,       // retry count
    priority: 10          // higher number = processed first
);
```

## Running the worker

The worker fetches jobs, deletes them after success, or reschedules them after a failure.

*Process one job and exit:*

```
vendor/bin/typo3 queue:work emails --once --retry-after=120 --backoff=60
```

*Run continuously (via Supervisor or TYPO3 Scheduler, `schedulable: true`):*

```
vendor/bin/typo3 queue:work emails --sleep=5 --retry-after=120 --backoff=60 --max-jobs=0
```

## Behaviour

- Jobs are processed as long as they are present in the table.
- A worker reserves a job (sets `reserved_at`, increments `attempts`). If it stays reserved past `retry-after`, it becomes available again.
- When `handle()` throws, the job is retried after `backoff` seconds until `maxAttempts` is reached; then it remains in the table with `last_error` set.
- Successful jobs are deleted immediately after processing.
