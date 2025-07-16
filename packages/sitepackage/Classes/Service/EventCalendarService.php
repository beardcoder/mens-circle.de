<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use DateTime;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event as ICalEvent;
use Eluceo\iCal\Domain\Entity\TimeZone;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Alarm\DisplayAction;
use Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger;
use Eluceo\iCal\Domain\ValueObject\DateTime as ICalDateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\Location as ICalLocation;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\SingletonInterface;

class EventCalendarService implements SingletonInterface
{
    private const string FORMAT_JSON = 'json';
    private const string FORMAT_ICS = 'ics';
    private const string FORMAT_JCAL = 'jcal';

    public function __construct(private readonly EventRepository $eventRepository) {}

    public function getFeed(string $format): string
    {
        $events = $this->getUpcomingEvents();

        return match ($format) {
            self::FORMAT_JSON => $this->generateJsonFeed($events),
            self::FORMAT_ICS => $this->generateIcsFeed($events),
            self::FORMAT_JCAL => $this->generateJcalFeed($events),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    public function getETag(string $format): string
    {
        $events = $this->getUpcomingEvents();
        $eventData = array_map(fn(Event $event) => [
            'uid' => $event->getUid(),
            'title' => $event->title,
            'startDate' => $event->startDate?->format('c'),
            'endDate' => $event->endDate?->format('c'),
            'description' => $event->description,
            'location' => $event->location->place,
        ], $events);

        return md5($format . json_encode($eventData));
    }

    private function getUpcomingEvents(): array
    {
        return $this->eventRepository->findNextEvents()->toArray();
    }

    private function generateJsonFeed(array $events): string
    {
        $jsonEvents = array_map(function (Event $event) {
            return [
                'uid' => $event->getUid(),
                'title' => $event->title,
                'description' => $event->description,
                'startDate' => $event->startDate?->format('c'),
                'endDate' => $event->endDate?->format('c'),
                'location' => [
                    'place' => $event->location->place,
                    'address' => $event->location->address,
                    'city' => $event->location->city,
                    'zip' => $event->location->zip,
                    'fullAddress' => $event->getFullAddress(),
                ],
                'isOnline' => $event->isOnline(),
                'callUrl' => $event->callUrl,
                'cancelled' => $event->isCancelled(),
                'attendanceMode' => $event->getRealAttendanceMode()->value,
            ];
        }, $events);

        return json_encode([
            'events' => $jsonEvents,
            'meta' => [
                'count' => count($jsonEvents),
                'generated' => new \DateTime()->format('c'),
                'ttl' => 3600, // 1 hour
            ],
        ], JSON_PRETTY_PRINT);
    }

    private function generateIcsFeed(array $events): string
    {
        $calendar = new Calendar();
        $calendar->addTimeZone($this->createTimeZone());

        // Add calendar properties via product identifier
        $calendar->setProductIdentifier('-//MensCircle//Event Calendar//DE');

        foreach ($events as $event) {
            $calendar->addEvent($this->createICalEvent($event));
        }

        $calendarFactory = new CalendarFactory();
        $calendarComponent = $calendarFactory->createCalendar($calendar);

        // Add custom properties
        $icsContent = $calendarComponent->__toString();
        $icsContent = str_replace(
            'END:VCALENDAR',
            "X-PUBLISHED-TTL:PT1H\r\nEND:VCALENDAR",
            $icsContent
        );

        return $icsContent;
    }

    private function generateJcalFeed(array $events): string
    {
        $jcalEvents = [];

        foreach ($events as $event) {
            $jcalEvents[] = [
                'vevent',
                [
                    'uid' => ['text', $this->generateEventUid($event)],
                    'dtstamp' => ['date-time', (new \DateTime())->format('Ymd\THis\Z')],
                    'dtstart' => ['date-time', $event->startDate?->format('Ymd\THis\Z')],
                    'dtend' => ['date-time', $event->endDate?->format('Ymd\THis\Z')],
                    'summary' => ['text', $event->title],
                    'description' => ['text', $event->description],
                    'location' => ['text', $this->formatLocation($event)],
                    'organizer' => ['cal-address', 'mailto:info@mens-circle.de'],
                    'status' => ['text', $event->isCancelled() ? 'CANCELLED' : 'CONFIRMED'],
                ],
                [
                    [
                        'valarm',
                        [
                            'action' => ['text', 'DISPLAY'],
                            'description' => ['text', 'Event reminder'],
                            'trigger' => ['duration', '-PT15M'],
                        ],
                        [],
                    ],
                ],
            ];
        }

        $jcal = [
            'vcalendar',
            [
                'version' => ['text', '2.0'],
                'prodid' => ['text', '-//MensCircle//Event Calendar//DE'],
                'name' => ['text', 'Mens Circle Events'],
                'x-published-ttl' => ['duration', 'PT1H'],
            ],
            $jcalEvents,
        ];

        return json_encode($jcal, JSON_PRETTY_PRINT);
    }

    private function createTimeZone(): TimeZone
    {
        return TimeZone::createFromPhpDateTimeZone(
            new \DateTimeZone('Europe/Berlin'),
            new \DateTime('2024-01-01'),
            new \DateTime('2026-12-31')
        );
    }

    private function createICalEvent(Event $event): ICalEvent
    {
        $icalEvent = new ICalEvent(new UniqueIdentifier($this->generateEventUid($event)));

        if ($event->startDate && $event->endDate) {
            $icalEvent->setOccurrence(new TimeSpan(
                new ICalDateTime($event->startDate, false),
                new ICalDateTime($event->endDate, false)
            ));
        }

        $icalEvent->setSummary($event->title);
        $icalEvent->setDescription($event->description);
        $icalEvent->setLocation(new ICalLocation($this->formatLocation($event)));

        $organizer = new Organizer(
            new EmailAddress('info@mens-circle.de'),
            'Mens Circle'
        );
        $icalEvent->setOrganizer($organizer);

        if ($event->isCancelled()) {
            $icalEvent->setStatus(EventStatus::CANCELLED());
        }

        // Add alarm (15 minutes before)
        $alarm = new Alarm(
            new DisplayAction('Event reminder'),
            new RelativeTrigger(\DateInterval::createFromDateString('-15 minutes'))
        );
        $icalEvent->addAlarm($alarm);

        return $icalEvent;
    }

    private function generateEventUid(Event $event): string
    {
        return sprintf('event-%d@mens-circle.de', $event->getUid());
    }

    private function formatLocation(Event $event): string
    {
        if ($event->isOnline()) {
            return 'Online Event: ' . $event->callUrl;
        }

        return $event->getFullAddress();
    }
}
