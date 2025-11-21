<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Queue;

class JobSerializer
{
    public function encode(JobInterface $job): string
    {
        return base64_encode(serialize($job));
    }

    public function decode(string $payload): JobInterface
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Queue payload could not be base64 decoded.');
        }

        $job = unserialize($decoded, [
            'allowed_classes' => true,
        ]);

        if (!$job instanceof JobInterface) {
            throw new \InvalidArgumentException('Queue payload does not contain a valid job.');
        }

        return $job;
    }
}
