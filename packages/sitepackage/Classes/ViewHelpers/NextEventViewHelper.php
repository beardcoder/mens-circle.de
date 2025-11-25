<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\ViewHelpers;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class NextEventViewHelper extends AbstractViewHelper
{
    public function __construct(protected EventRepository $eventRepository)
    {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws InvalidQueryException
     */
    #[\Override]
    public function render(): DomainObjectInterface
    {
        return $this->eventRepository->findNextUpcomingEvent()->getFirst();
    }
}
