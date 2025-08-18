<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\ViewHelpers;

use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class NextEventViewHelper extends AbstractViewHelper
{
    public function __construct(protected EventRepository $eventRepository) {}

    /**
     * @throws \DateMalformedStringException
     * @throws InvalidQueryException
     */
    public function render(): QueryResultInterface
    {
        return $this->eventRepository->findNextUpcomingEvent();
    }
}
