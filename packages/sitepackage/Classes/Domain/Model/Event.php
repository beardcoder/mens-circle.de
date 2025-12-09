<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Model;

use MensCircle\Sitepackage\Enum\EventAttendanceModeEnum;
use MensCircle\Sitepackage\Enum\EventStatusEnum;
use Spatie\SchemaOrg\Event as EventSchema;
use Spatie\SchemaOrg\ItemAvailability;
use Spatie\SchemaOrg\Schema;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Attribute\ORM;
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

    #[ORM\Lazy()]
    protected FileReference|LazyLoadingProxy|null $image = null;

    /**
     * @var ObjectStorage<Participant>
     */
    #[ORM\Lazy()]
    #[ORM\Cascade(value: 'remove')]
    public ObjectStorage $participants {
        get => $this->participants;
        set(ObjectStorage $value) {
            $this->participants = $value;
        }
    }

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->participants = new ObjectStorage();
    }

    /**
     * Computed property: Check if event is online.
     */
    public bool $isOnline {
        get => $this->realAttendanceMode === EventAttendanceModeEnum::ONLINE;
    }

    /**
     * Computed property: Check if event is cancelled.
     */
    public bool $isCancelled {
        get => $this->cancelled;
    }

    /**
     * Computed property: Get long title with date.
     */
    public string $longTitle {
        get {
            $date = $this->startDate?->format('d.m.Y') ?? '';

            return $date !== '' ? ("{$this->title} am {$date}") : $this->title;
        }
    }

    /**
     * Computed property: Get real attendance mode enum.
     */
    public EventAttendanceModeEnum $realAttendanceMode {
        get => EventAttendanceModeEnum::from($this->attendanceMode);
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

        $place = $this->isOffline ? Schema::place()
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
            ->eventAttendanceMode($this->realAttendanceMode->getDescription())
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

    public bool $isOffline {
        get => $this->realAttendanceMode === EventAttendanceModeEnum::OFFLINE;
    }

    public string $fullAddress {
        get => "{$this->location->address}, {$this->location->zip} {$this->location->city}, Deutschland";
    }

    public function getImage(): ?FileReference
    {
        if ($this->image instanceof FileReference) {
            return $this->image;
        }

        $image = $this->image->_loadRealInstance();

        if ($image instanceof FileReference) {
            return $image;
        }

        return null;
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
