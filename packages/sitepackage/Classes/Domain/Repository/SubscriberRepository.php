<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Subscriber;
use MensCircle\Sitepackage\Enum\SubscriberStatus;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Subscriber>
 */
class SubscriberRepository extends Repository
{
    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING,
    ];

    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findByEmail(string $email): ?Subscriber
    {
        $query = $this->createQuery();
        $query->matching($query->equals('email', $email));

        return $query->execute()->getFirst();
    }

    public function findByToken(string $token): ?Subscriber
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('token', $token),
                $query->equals('status', SubscriberStatus::Pending->value),
            ),
        );

        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResultInterface<Subscriber>
     */
    public function findActive(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', SubscriberStatus::Confirmed->value));

        return $query->execute();
    }

    public function countActive(): int
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', SubscriberStatus::Confirmed->value));

        return $query->count();
    }

    /**
     * @return QueryResultInterface<Subscriber>
     */
    public function findByStatus(SubscriberStatus $status): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', $status->value));

        return $query->execute();
    }

    /**
     * @return array{total: int, confirmed: int, pending: int, unsubscribed: int}
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->countAll(),
            'confirmed' => $this->countByStatus(SubscriberStatus::Confirmed),
            'pending' => $this->countByStatus(SubscriberStatus::Pending),
            'unsubscribed' => $this->countByStatus(SubscriberStatus::Unsubscribed),
        ];
    }

    private function countByStatus(SubscriberStatus $status): int
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', $status->value));

        return $query->count();
    }
}
