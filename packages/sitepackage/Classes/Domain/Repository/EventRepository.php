<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

use function Symfony\Component\Clock\now;

/**
 * @extends Repository<Event>
 */
class EventRepository extends Repository
{
    use StoragePageAgnosticTrait;

    protected $defaultOrderings = [
        'startDate' => QueryInterface::ORDER_ASCENDING,
    ];

    protected $objectType = Event::class;

    /**
     * @return QueryResultInterface<int, Event>
     *
     * @throws \DateMalformedStringException
     * @throws InvalidQueryException
     */
    public function findNextEvents(): QueryResultInterface
    {
        $query = $this->createQuery();

        return $query
            ->matching($query->logicalAnd($query->greaterThanOrEqual('startDate', now())))
            ->execute()
        ;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws InvalidQueryException
     */
    public function findNextUpcomingEvent(): ?Event
    {
        $query = $this->createQuery();

        return $query
            ->matching($query->greaterThanOrEqual('startDate', now()))
            ->setLimit(1)
            ->execute()
            ->getFirst()
        ;
    }
}
