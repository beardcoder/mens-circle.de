<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Event model for men's circle events
 */
class Event extends AbstractEntity
{
    protected string $title = '';

    protected string $slug = '';

    protected string $description = '';

    protected ?FileReference $image = null;

    protected ?\DateTime $eventDate = null;

    protected ?int $startTime = null;

    protected ?int $endTime = null;

    protected string $location = 'Straubing';

    protected string $street = '';

    protected string $postalCode = '';

    protected string $city = '';

    protected string $locationDetails = '';

    protected int $maxParticipants = 8;

    protected string $costBasis = 'Auf Spendenbasis';

    protected bool $isPublished = false;

    /**
     * @var ObjectStorage<EventRegistration>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $registrations;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->registrations = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getEventDate(): ?\DateTime
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTime $eventDate): self
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getStartTime(): ?int
    {
        return $this->startTime;
    }

    public function setStartTime(?int $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?int
    {
        return $this->endTime;
    }

    public function setEndTime(?int $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getStartTimeFormatted(): string
    {
        if ($this->startTime === null) {
            return '';
        }
        $hours = floor($this->startTime / 3600);
        $minutes = floor(($this->startTime % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getEndTimeFormatted(): string
    {
        if ($this->endTime === null) {
            return '';
        }
        $hours = floor($this->endTime / 3600);
        $minutes = floor(($this->endTime % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getLocationDetails(): string
    {
        return $this->locationDetails;
    }

    public function setLocationDetails(string $locationDetails): self
    {
        $this->locationDetails = $locationDetails;
        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            trim($this->postalCode . ' ' . $this->city),
        ]);
        return implode(', ', $parts);
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function getCostBasis(): string
    {
        return $this->costBasis;
    }

    public function setCostBasis(string $costBasis): self
    {
        $this->costBasis = $costBasis;
        return $this;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        return $this;
    }


    /**
     * @return ObjectStorage<EventRegistration>
     */
    public function getRegistrations(): ObjectStorage
    {
        return $this->registrations;
    }

    /**
     * @param ObjectStorage<EventRegistration> $registrations
     */
    public function setRegistrations(ObjectStorage $registrations): self
    {
        $this->registrations = $registrations;
        return $this;
    }

    public function addRegistration(EventRegistration $registration): self
    {
        $this->registrations->attach($registration);
        return $this;
    }

    public function removeRegistration(EventRegistration $registration): self
    {
        $this->registrations->detach($registration);
        return $this;
    }

    /**
     * Get only confirmed registrations
     *
     * @return array<EventRegistration>
     */
    public function getConfirmedRegistrations(): array
    {
        $confirmed = [];
        foreach ($this->registrations as $registration) {
            if ($registration->getStatus() === 'confirmed') {
                $confirmed[] = $registration;
            }
        }
        return $confirmed;
    }

    public function getConfirmedRegistrationsCount(): int
    {
        return count($this->getConfirmedRegistrations());
    }

    public function getAvailableSpots(): int
    {
        return max(0, $this->maxParticipants - $this->getConfirmedRegistrationsCount());
    }

    public function isFull(): bool
    {
        return $this->getAvailableSpots() <= 0;
    }

    public function isPast(): bool
    {
        if (!$this->eventDate instanceof \DateTime) {
            return false;
        }
        return $this->eventDate < new \DateTime('today');
    }

    /**
     * Generate iCal content for calendar export
     */
    public function generateICalContent(): string
    {
        $uid = sprintf('event-%d@mens-circle.de', $this->uid);
        $now = new \DateTime();
        $dtstamp = $now->format('Ymd\THis\Z');

        $dtstart = $this->eventDate?->format('Ymd');
        if ($this->startTime !== null && $dtstart) {
            $hours = floor($this->startTime / 3600);
            $minutes = floor(($this->startTime % 3600) / 60);
            $dtstart .= sprintf('T%02d%02d00', $hours, $minutes);
        }

        $dtend = $this->eventDate?->format('Ymd');
        if ($this->endTime !== null && $dtend) {
            $hours = floor($this->endTime / 3600);
            $minutes = floor(($this->endTime % 3600) / 60);
            $dtend .= sprintf('T%02d%02d00', $hours, $minutes);
        }

        $location = $this->location;
        if ($this->getFullAddress() !== '' && $this->getFullAddress() !== '0') {
            $location .= ', ' . $this->getFullAddress();
        }

        $description = strip_tags($this->description);
        $description = str_replace(["\r\n", "\n", "\r"], '\n', $description);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Mens Circle//Event//DE',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'DTSTART:' . $dtstart,
            'DTEND:' . $dtend,
            'SUMMARY:' . $this->title,
            'DESCRIPTION:' . $description,
            'LOCATION:' . $location,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines);
    }
}
