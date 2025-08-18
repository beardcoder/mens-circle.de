<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

use function Symfony\Component\Clock\now;

class EventRepository extends Repository
{
    use StoragePageAgnosticTrait;

    /** @var array<string,int> */
    protected array $defaultOrderings = [
        'startDate' => QueryInterface::ORDER_ASCENDING,
    ];

    public function findNextEvents(): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query
                ->matching($query->logicalAnd($query->greaterThanOrEqual('startDate', now())))
                ->execute();
    }

    public function findNextUpcomingEvent(): ?Event
    {
        $query = $this->createQuery();

        return $query
            ->matching($query->greaterThanOrEqual('startDate', now()))
            ->setLimit(1)
            ->execute()
            ->getFirst();
    }
}
