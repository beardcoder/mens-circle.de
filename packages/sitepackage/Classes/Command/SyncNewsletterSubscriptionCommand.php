<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Command;

use Doctrine\DBAL\Exception;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class SyncNewsletterSubscriptionCommand extends Command
{
    protected const string SOURCE_TABLE = 'fe_users';
    protected const string TARGET_TABLE = 'tx_sitepackage_domain_model_subscription';

    public function __construct(protected readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sync all fe users with newsletter subscription');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $symfonyStyle->title('Sync newsletter subscriptions from fe_users');

        try {
            $feusers = $this->getAllFeUsers();
            $subscriptions = $this->getAllSubscriptions();
        } catch (Exception $e) {
            $symfonyStyle->error('Failed to read from database: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Build a lowercase set of existing subscription emails for case-insensitive comparison
        $subscriptionEmails = array_column($subscriptions, 'email');
        $subscriptionEmailsLower = array_map(static fn ($e) => \is_string($e) ? strtolower($e) : '', $subscriptionEmails);

        // Determine users that need a subscription; skip empty/invalid emails and deduplicate by email
        $toSyncByEmail = [];
        foreach ($feusers as $user) {
            $email = (string) ($user['email'] ?? '');
            $emailLower = strtolower($email);
            if ($emailLower === '') {
                continue;
            }
            if (filter_var($emailLower, \FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            if (\in_array($emailLower, $subscriptionEmailsLower, true)) {
                continue; // already subscribed
            }
            if (!isset($toSyncByEmail[$emailLower])) {
                $toSyncByEmail[$emailLower] = $user;
            }
        }

        if ($toSyncByEmail === []) {
            $symfonyStyle->success('No users to sync. All fe_users already have subscriptions.');

            return Command::SUCCESS;
        }

        $connection = $this->connectionPool->getConnectionForTable(self::TARGET_TABLE);
        $synced = [];

        $symfonyStyle->section(\sprintf('Creating %d missing subscription(s)...', \count($toSyncByEmail)));
        $symfonyStyle->progressStart(\count($toSyncByEmail));

        try {
            $connection->beginTransaction();
            foreach ($toSyncByEmail as $emailLower => $user) {
                $firstName = (string) ($user['first_name'] ?? '');
                $lastName = (string) ($user['last_name'] ?? '');
                $now = time();

                $connection->insert(
                    self::TARGET_TABLE,
                    [
                        'email' => (string) $user['email'],
                        'first_name' => $firstName,
                        'last_name' => $lastName, // fixed: use last_name, not first_name
                        'status' => SubscriptionStatusEnum::Active->value,
                        'fe_user' => (int) $user['uid'],
                        'pid' => (int) ($user['pid'] ?? 0),
                        'crdate' => $now,
                        'tstamp' => $now,
                    ]
                );

                $synced[] = [
                    'uid' => (int) $user['uid'],
                    'email' => (string) $user['email'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ];

                $symfonyStyle->progressAdvance();
            }
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $symfonyStyle->progressFinish();
            $symfonyStyle->error('Failed to sync subscriptions: '.$e->getMessage());

            return Command::FAILURE;
        }

        $symfonyStyle->progressFinish();

        // Output which users were synced (email and name for clarity)
        $symfonyStyle->section('Synced users');
        $symfonyStyle->listing(array_map(
            static fn (array $u): string => \sprintf(
                '%s <%s> (uid: %d)',
                trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')) ?: '(no name)',
                $u['email'] ?? '',
                $u['uid'] ?? 0
            ),
            $synced
        ));

        $symfonyStyle->success(\sprintf('Done. Synced %d subscription(s).', \count($synced)));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function getAllFeUsers(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::SOURCE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->from(self::SOURCE_TABLE)
            ->select('uid', 'email', 'first_name', 'last_name', 'pid')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function getAllSubscriptions(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TARGET_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->from(self::TARGET_TABLE)
            ->select('email')
            ->executeQuery()
            ->fetchAllAssociative()
        ;
    }
}
