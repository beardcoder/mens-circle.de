<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\EventRegistration;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for EventRegistration entities
 *
 * @extends Repository<EventRegistration>
 */
class EventRegistrationRepository extends Repository
{
    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Find registrations by event
     *
     * @return QueryResultInterface<int, EventRegistration>
     */
    public function findByEvent(Event $event): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('event', $event));

        return $query->execute();
    }

    /**
     * Find confirmed registrations by event
     *
     * @return QueryResultInterface<int, EventRegistration>
     */
    public function findConfirmedByEvent(Event $event): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', EventRegistration::STATUS_CONFIRMED)
            )
        );

        return $query->execute();
    }

    /**
     * Find registration by event and email
     */
    public function findByEventAndEmail(Event $event, string $email): ?EventRegistration
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('email', $email)
            )
        );

        /** @var EventRegistration|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }

    /**
     * Count registrations by event
     */
    public function countByEvent(Event $event): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->equals('event', $event)
        );

        return $query->count();
    }

    /**
     * Count confirmed registrations by event
     */
    public function countConfirmedByEvent(Event $event): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('event', $event),
                $query->equals('status', EventRegistration::STATUS_CONFIRMED)
            )
        );

        return $query->count();
    }

    /**
     * Count all registrations
     */
    #[\Override]
    public function countAll(): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->count();
    }

    /**
     * Find all registrations for backend
     *
     * @return QueryResultInterface<int, EventRegistration>
     */
    public function findAllForBackend(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $query->execute();
    }
}
