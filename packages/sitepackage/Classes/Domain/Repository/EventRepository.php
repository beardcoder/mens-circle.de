<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Event;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for Event entities
 *
 * @extends Repository<Event>
 */
class EventRepository extends Repository
{
    protected $defaultOrderings = [
        'eventDate' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Find all published events
     *
     * @return QueryResultInterface<Event>
     */
    public function findPublished(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('isPublished', true)
        );
        $query->setOrderings([
            'eventDate' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute();
    }

    /**
     * Find upcoming published events
     *
     * @return QueryResultInterface<Event>
     */
    public function findUpcoming(int $limit = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->greaterThanOrEqual('eventDate', new \DateTime('today')->getTimestamp())
            )
        );
        $query->setOrderings([
            'eventDate' => QueryInterface::ORDER_ASCENDING,
        ]);

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    /**
     * Find past published events
     *
     * @return QueryResultInterface<Event>
     */
    public function findPast(int $limit = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->lessThan('eventDate', new \DateTime('today')->getTimestamp())
            )
        );
        $query->setOrderings([
            'eventDate' => QueryInterface::ORDER_DESCENDING,
        ]);

        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    /**
     * Find event by slug
     */
    public function findBySlug(string $slug): ?Event
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('slug', $slug)
        );

        /** @var Event|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }

    /**
     * Find published event by slug
     */
    public function findPublishedBySlug(string $slug): ?Event
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('slug', $slug),
                $query->equals('isPublished', true)
            )
        );

        /** @var Event|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }

    /**
     * Find all events including unpublished (for backend)
     *
     * @return QueryResultInterface<Event>
     */
    public function findAllForBackend(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings([
            'eventDate' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $query->execute();
    }

    /**
     * Count all events
     */
    #[\Override]
    public function countAll(): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->count();
    }

    /**
     * Count upcoming events
     */
    public function countUpcoming(): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->greaterThanOrEqual('eventDate', new \DateTime('today')->getTimestamp())
            )
        );

        return $query->count();
    }

    /**
     * Count past events
     */
    public function countPast(): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->lessThan('eventDate', new \DateTime('today')->getTimestamp())
            )
        );

        return $query->count();
    }
}
