<?php

declare(strict_types=1);

namespace Beardcoder\Queue\Command;

use Beardcoder\Queue\Queue\QueueWorker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkQueueCommand extends Command
{
    protected static string $defaultName = 'queue:work';

    protected static string $defaultDescription = 'Process queued jobs using the database backend (successful jobs are removed).';

    public function __construct(
        private readonly QueueWorker $worker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name', 'default')
            ->addOption('once', 'o', InputOption::VALUE_NONE, 'Process only one job and exit')
            ->addOption('max-jobs', null, InputOption::VALUE_OPTIONAL, 'Stop after processing the given amount of jobs (0 = unlimited)', '0')
            ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Seconds to sleep when the queue is empty', '5')
            ->addOption('retry-after', null, InputOption::VALUE_OPTIONAL, 'Seconds after which a reserved job is retried', '90')
            ->addOption('backoff', null, InputOption::VALUE_OPTIONAL, 'Delay in seconds before retrying a failed job', '30')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = (string) $input->getArgument('queue');
        $sleep = max(0, (int) $input->getOption('sleep'));
        $retryAfter = max(0, (int) $input->getOption('retry-after'));
        $backoff = max(0, (int) $input->getOption('backoff'));

        if ($input->getOption('once') === true) {
            $processed = $this->worker->runOnce($queue, $retryAfter, $backoff);
            $message = $processed ? '<info>Processed 1 job.</info>' : '<comment>No job available.</comment>';
            $output->writeln($message);

            return Command::SUCCESS;
        }

        $maxJobs = (int) $input->getOption('max-jobs');
        if ($maxJobs < 0) {
            $maxJobs = 0;
        }

        $count = $this->worker->work(
            queue: $queue,
            maxJobs: $maxJobs,
            sleepSeconds: $sleep,
            retryAfterSeconds: $retryAfter,
            backoffSeconds: $backoff,
        );

        $output->writeln(\sprintf('Processed %d job(s) from queue "%s".', $count, $queue));

        return Command::SUCCESS;
    }
}
