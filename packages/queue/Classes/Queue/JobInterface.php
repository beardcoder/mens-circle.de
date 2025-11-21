<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

interface JobInterface
{
    /**
     * Execute the job. Throw an exception to signal a failure.
     */
    public function handle(): void;
}
