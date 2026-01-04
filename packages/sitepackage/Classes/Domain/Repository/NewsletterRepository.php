<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Newsletter;
use MensCircle\Sitepackage\Enum\NewsletterStatus;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Newsletter>
 */
class NewsletterRepository extends Repository
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

    /**
     * @return QueryResultInterface<Newsletter>
     */
    public function findDrafts(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', NewsletterStatus::Draft->value));

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<Newsletter>
     */
    public function findScheduled(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('status', NewsletterStatus::Scheduled->value),
                $query->lessThanOrEqual('scheduledAt', time()),
            ),
        );

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<Newsletter>
     */
    public function findSent(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', NewsletterStatus::Sent->value));
        $query->setOrderings(['sentAt' => QueryInterface::ORDER_DESCENDING]);

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<Newsletter>
     */
    public function findByStatus(NewsletterStatus $status): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', $status->value));

        return $query->execute();
    }

    /**
     * @return array{total: int, drafts: int, sent: int, scheduled: int}
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->countAll(),
            'drafts' => $this->countByStatus(NewsletterStatus::Draft),
            'sent' => $this->countByStatus(NewsletterStatus::Sent),
            'scheduled' => $this->countByStatus(NewsletterStatus::Scheduled),
        ];
    }

    private function countByStatus(NewsletterStatus $status): int
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', $status->value));

        return $query->count();
    }
}
