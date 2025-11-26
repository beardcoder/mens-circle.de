<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model;

use MensCircle\Sitepackage\Enum\EventAttendanceModeEnum;
use MensCircle\Sitepackage\Enum\EventStatusEnum;
use Spatie\SchemaOrg\Event as EventSchema;
use Spatie\SchemaOrg\ItemAvailability;
use Spatie\SchemaOrg\Schema;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Service\ImageService;

class Event extends AbstractEntity
{
    public string $slug;

    public string $title;

    public string $description;

    public ?\DateTime $startDate = null;

    public ?\DateTime $endDate = null;

    public ?\DateTime $crdate = null;

    public string $callUrl = '';

    public bool $cancelled = false;

    public int $attendanceMode = 0;

    public Location $location;

    #[Extbase\ORM\Lazy()]
    protected FileReference|LazyLoadingProxy|null $image = null;

    /**
     * @var ObjectStorage<Participant>
     */
    #[Extbase\ORM\Lazy()]
    #[Extbase\ORM\Cascade([
        'value' => 'remove',
    ])]
    protected ObjectStorage $participants;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->participants = new ObjectStorage();
    }

    public function isOnline(): bool
    {
        return $this->getRealAttendanceMode() === EventAttendanceModeEnum::ONLINE;
    }

    public function getRealAttendanceMode(): EventAttendanceModeEnum
    {
        return EventAttendanceModeEnum::from($this->attendanceMode);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function getLongTitle(): string
    {
        $date = $this->startDate?->format('d.m.Y') ?? '';

        return $date !== '' ? ($this->title.' am '.$date) : $this->title;
    }

    public function buildSchema(UriBuilder $uriBuilder): EventSchema
    {
        $thisUrl = $uriBuilder->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageUid(3)
            ->uriFor('detail', [
                'event' => $this->uid,
            ])
        ;

        $imageService = GeneralUtility::makeInstance(ImageService::class);

        $imageRef = $this->getImage();
        $processedFile = $imageRef instanceof FileReference ? $imageService->applyProcessingInstructions(
            $imageRef->getOriginalResource(),
            [
                'width' => '600c',
                'height' => '600c',
            ],
        ) : null;

        $place = $this->isOffline() ? Schema::place()
            ->name($this->location->place)
            ->address(
                Schema::postalAddress()
                    ->streetAddress($this->location->address)
                    ->addressLocality($this->location->city)
                    ->postalCode($this->location->zip)
                    ->addressCountry('DE'),
            ) : Schema::place()->url($this->callUrl);

        $imageUri = $processedFile ? $imageService->getImageUri($processedFile, true) : null;
        $baseUrl = $uriBuilder->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageUid(1)
            ->buildFrontendUri()
        ;
        $eventStatus = Schema::eventStatusType()->setProperty('@id', EventStatusEnum::EventScheduled->value);
        $availability = Schema::itemAvailability()->setProperty('@id', ItemAvailability::InStock);

        return Schema::event()
            ->name(\sprintf('%s am %s', $this->title, $this->startDate->format('d.m.Y')))
            ->description($this->description)
            ->image($imageUri)
            ->startDate($this->startDate)
            ->endDate($this->endDate)
            ->eventAttendanceMode($this->getRealAttendanceMode()->getDescription())
            ->eventStatus($eventStatus)
            ->location($place)
            ->offers(
                Schema::offer()
                    ->validFrom($this->crdate)
                    ->price(0)
                    ->availability($availability)
                    ->url($thisUrl)
                    ->priceCurrency('EUR'),
            )
            ->organizer(Schema::person()->name('Markus Sommer')->url($baseUrl))
            ->performer(Schema::person()->name('Markus Sommer')->url($baseUrl))
        ;
    }

    public function getImage(): ?FileReference
    {
        if ($this->image instanceof LazyLoadingProxy) {
            /** @var FileReference $image */
            $image = $this->image->_loadRealInstance();
            $this->image = $image;
        }

        return $this->image;
    }

    public function isOffline(): bool
    {
        return $this->getRealAttendanceMode() === EventAttendanceModeEnum::OFFLINE;
    }

    public function getFullAddress(): string
    {
        return \sprintf('%s, %s %s, Deutschland', $this->location->address, $this->location->zip, $this->location->city);
    }

    /**
     * @param ObjectStorage<Participant> $objectStorage
     */
    public function setParticipants(ObjectStorage $objectStorage): void
    {
        $this->participants = $objectStorage;
    }

    /**
     * @return ObjectStorage<Participant>
     */
    public function getParticipants(): ObjectStorage
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): void
    {
        $this->participants->attach($participant);
    }

    public function removeParticipant(Participant $participant): void
    {
        $this->participants->detach($participant);
    }
}
