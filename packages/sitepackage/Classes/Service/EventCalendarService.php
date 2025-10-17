<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

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

final class EventCalendarService implements SingletonInterface
{
    private const string FORMAT_JSON = 'json';

    private const string FORMAT_ICS = 'ics';

    private const string FORMAT_JCAL = 'jcal';

    // iOS/Android refresh intervals - optimized for battery and data usage
    private const string TTL_DURATION = 'PT1H'; // 1 hour for mobile compatibility

    private const int CACHE_MAX_AGE = 3600; // Matches TTL for consistency

    // Calendar metadata for iOS/Android compatibility
    private const string CALENDAR_NAME = 'Mens Circle Veranstaltungen';

    private const string CALENDAR_DESCRIPTION = 'Bevorstehende Veranstaltungen der Mens Circle Community';

    private const string PRODID = '-//MensCircle//Event Calendar v2.0//DE';

    private const string ORGANIZER_EMAIL = 'hallo@mens-circle.de';

    private const string ORGANIZER_NAME = 'Mens Circle (Markus Sommer)';

    // Timezone constants
    private const string DEFAULT_TIMEZONE = 'Europe/Berlin';

    // Centralized alarm configuration
    private const string PRIMARY_ALARM_TEXT = 'Event reminder';
    private const string SECONDARY_ALARM_TEXT = 'Event starting in 1 hour';
    private const string PRIMARY_ALARM_OFFSET = '-15 minutes';
    private const string SECONDARY_ALARM_OFFSET = '-1 hour';

    private ?array $cachedEvents = null;

    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {
    }

    /**
     * @return string Rendered feed content based on requested format
     */
    public function getFeed(string $format): string
    {
        $events = $this->getUpcomingEvents();

        return match ($format) {
            self::FORMAT_JSON => $this->generateJsonFeed($events),
            self::FORMAT_ICS => $this->generateIcsFeed($events),
            self::FORMAT_JCAL => $this->generateJcalFeed($events),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}", 1278997658),
        };
    }

    public function getETag(string $format): string
    {
        $events = $this->getUpcomingEvents();

        // Create deterministic hash based on event data and format
        $eventData = $this->extractEventDataForHashing($events);
        $hashSource = $format.serialize($eventData).self::TTL_DURATION;

        return md5($hashSource);
    }

    /**
     * @return array<int, Event>
     */
    private function getUpcomingEvents(): array
    {
        if ($this->cachedEvents === null) {
            $this->cachedEvents = $this->eventRepository->findNextEvents()->toArray();
        }

        return $this->cachedEvents;
    }

    /**
     * Build a sorted, minimal representation of events to stabilize ETag generation.
     *
     * @param array<int, Event> $events
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractEventDataForHashing(array $events): array
    {
        $data = array_map(fn (Event $event): array => [
            'uid' => $event->getUid(),
            'title' => trim($event->title),
            'startDate' => $event->startDate?->format('c') ?? '',
            'endDate' => $event->endDate?->format('c') ?? '',
            'description' => trim($event->description),
            'location' => $this->sanitizeLocationForHash($event),
            'cancelled' => $event->isCancelled(),
            'attendanceMode' => $event->getRealAttendanceMode()
                ->value,
        ], $events);

        usort($data, static function (array $a, array $b): int {
            // Sort by startDate then uid for stable ordering
            $left = [$a['startDate'], $a['uid']];
            $right = [$b['startDate'], $b['uid']];

            return $left <=> $right;
        });

        return $data;
    }

    private function sanitizeLocationForHash(Event $event): string
    {
        if ($event->isOnline()) {
            return 'online:'.trim($event->callUrl);
        }

        return trim($event->getFullAddress());
    }

    private function generateJsonFeed(array $events): string
    {
        $jsonEvents = array_map([$this, 'transformEventToJson'], $events);

        $feedData = [
            'events' => $jsonEvents,
            'meta' => [
                'count' => \count($jsonEvents),
                'generated' => new \DateTime()->format('c'),
                'ttl' => self::CACHE_MAX_AGE,
                'timezone' => self::DEFAULT_TIMEZONE,
                'version' => '2.0',
            ],
        ];

        return json_encode($feedData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEventToJson(Event $event): array
    {
        return [
            'uid' => $event->getUid(),
            'title' => $this->sanitizeText($event->title),
            'description' => $this->sanitizeText($event->description),
            'startDate' => $event->startDate?->format('c'),
            'endDate' => $event->endDate?->format('c'),
            'location' => $this->transformLocationToJson($event),
            'isOnline' => $event->isOnline(),
            'callUrl' => $event->isOnline() ? trim($event->callUrl) : null,
            'cancelled' => $event->isCancelled(),
            'attendanceMode' => $event->getRealAttendanceMode()
                ->value,
            'organizer' => [
                'name' => self::ORGANIZER_NAME,
                'email' => self::ORGANIZER_EMAIL,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformLocationToJson(Event $event): array
    {
        return [
            'place' => $this->sanitizeText($event->location->place),
            'address' => $this->sanitizeText($event->location->address),
            'city' => $this->sanitizeText($event->location->city),
            'zip' => $this->sanitizeText($event->location->zip),
            'fullAddress' => $this->sanitizeText($event->getFullAddress()),
            'coordinates' => [
                'latitude' => $event->location->latitude ?: null,
                'longitude' => $event->location->longitude ?: null,
            ],
        ];
    }

    private function generateIcsFeed(array $events): string
    {
        $calendar = $this->createOptimizedCalendar();

        foreach ($events as $event) {
            if ($this->isValidEvent($event)) {
                $calendar->addEvent($this->createOptimizedICalEvent($event));
            }
        }

        $calendarFactory = new CalendarFactory();
        $calendarComponent = $calendarFactory->createCalendar($calendar);

        // Add mobile-optimized headers for iOS/Android compatibility
        return $this->addMobileOptimizedHeaders($calendarComponent->__toString());
    }

    private function createOptimizedCalendar(): Calendar
    {
        $calendar = new Calendar();

        // Essential for iOS/Android: Add timezone first
        $calendar->addTimeZone($this->createOptimizedTimeZone());

        // iOS/Android compatible product identifier
        $calendar->setProductIdentifier(self::PRODID);

        return $calendar;
    }

    private function createOptimizedTimeZone(): TimeZone
    {
        // Create timezone with dynamic range for better mobile device support
        $dateTimeZone = new \DateTimeZone(self::DEFAULT_TIMEZONE);
        $now = new \DateTimeImmutable('now', $dateTimeZone);
        $start = $now->setDate((int) $now->format('Y') - 1, 1, 1)->setTime(0, 0);
        $end = $now->setDate((int) $now->format('Y') + 3, 12, 31)->setTime(23, 59, 59);

        return TimeZone::createFromPhpDateTimeZone(
            $dateTimeZone,
            $start,
            $end,
        );
    }

    private function createOptimizedICalEvent(Event $event): ICalEvent
    {
        $icalEvent = new ICalEvent(new UniqueIdentifier($this->generateStableEventUid($event)));

        // Set occurrence with proper timezone handling for mobile devices
        if ($event->startDate instanceof \DateTime && $event->endDate instanceof \DateTime) {
            $icalEvent->setOccurrence(new TimeSpan(
                new ICalDateTime($event->startDate, false), // Use floating time for local events
                new ICalDateTime($event->endDate, false),
            ));
        }

        // Sanitize and set text properties (essential for iOS/Android)
        $icalEvent->setSummary($this->sanitizeText($event->title));
        $icalEvent->setDescription($this->sanitizeText($event->description));
        $icalEvent->setLocation(new ICalLocation($this->formatLocationForIcs($event)));

        // Set organizer (improves compatibility with mobile calendar apps)
        $organizer = new Organizer(new EmailAddress(self::ORGANIZER_EMAIL), self::ORGANIZER_NAME);
        $icalEvent->setOrganizer($organizer);

        // Set status (essential for iOS/Android event handling)
        $icalEvent->setStatus($event->isCancelled() ? EventStatus::CANCELLED() : EventStatus::CONFIRMED());

        // Add optimized alarms for mobile notifications
        $this->addMobileOptimizedAlarms($icalEvent);

        return $icalEvent;
    }

    private function addMobileOptimizedAlarms(ICalEvent $calEvent): void
    {
        // Primary alarm: 15 minutes before (iOS/Android standard)
        $primaryAlarm = new Alarm(
            new DisplayAction($this->sanitizeText(self::PRIMARY_ALARM_TEXT)),
            new RelativeTrigger(\DateInterval::createFromDateString(self::PRIMARY_ALARM_OFFSET)),
        );
        $calEvent->addAlarm($primaryAlarm);

        // Secondary alarm: 1 hour before (for important events)
        $secondaryAlarm = new Alarm(
            new DisplayAction($this->sanitizeText(self::SECONDARY_ALARM_TEXT)),
            new RelativeTrigger(\DateInterval::createFromDateString(self::SECONDARY_ALARM_OFFSET)),
        );
        $calEvent->addAlarm($secondaryAlarm);
    }

    private function addMobileOptimizedHeaders(string $icsContent): string
    {
        $headers = [
            // Essential for iOS/Android auto-refresh
            'X-PUBLISHED-TTL:'.self::TTL_DURATION,
            // Apple Calendar specific refresh interval
            'REFRESH-INTERVAL;VALUE=DURATION:'.self::TTL_DURATION,
            // Calendar name for mobile display
            'X-WR-CALNAME:'.self::CALENDAR_NAME,
            // Calendar description for mobile display
            'X-WR-CALDESC:'.self::CALENDAR_DESCRIPTION,
            // Timezone for mobile compatibility
            'X-WR-TIMEZONE:'.self::DEFAULT_TIMEZONE,
        ];

        $headerString = implode("\r\n", $headers)."\r\n";

        // Ensure METHOD and CALSCALE exist only once, after VERSION
        if (str_contains($icsContent, "VERSION:2.0\r\n") && !str_contains($icsContent, 'METHOD:PUBLISH')) {
            $icsContent = str_replace(
                "VERSION:2.0\r\n",
                "VERSION:2.0\r\nMETHOD:PUBLISH\r\nCALSCALE:GREGORIAN\r\n",
                $icsContent,
            );
        }

        // Append custom headers once before END:VCALENDAR
        if (!str_contains($icsContent, 'X-WR-CALNAME:')) {
            return str_replace('END:VCALENDAR', $headerString.'END:VCALENDAR', $icsContent);
        }

        return $icsContent;
    }

    private function generateJcalFeed(array $events): string
    {
        $jcalEvents = array_map([$this, 'transformEventToJcal'], $events);

        $jcal = [
            'vcalendar',
            [
                'version' => ['text', '2.0'],
                'prodid' => ['text', self::PRODID],
                'calscale' => ['text', 'GREGORIAN'],
                'method' => ['text', 'PUBLISH'],
                'x-wr-calname' => ['text', self::CALENDAR_NAME],
                'x-wr-caldesc' => ['text', self::CALENDAR_DESCRIPTION],
                'x-wr-timezone' => ['text', self::DEFAULT_TIMEZONE],
                'x-published-ttl' => ['duration', self::TTL_DURATION],
                'refresh-interval' => ['duration', self::TTL_DURATION],
            ],
            $jcalEvents,
        ];

        return json_encode($jcal, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
    }

    private function transformEventToJcal(Event $event): array
    {
        $dtStampUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Ymd\THis\Z');

        return [
            'vevent',
            [
                'uid' => ['text', $this->generateStableEventUid($event)],
                'dtstamp' => ['date-time', $dtStampUtc],
                'dtstart' => ['date-time', $event->startDate?->format('Ymd\THis')],
                'dtend' => ['date-time', $event->endDate?->format('Ymd\THis')],
                'summary' => ['text', $this->sanitizeText($event->title)],
                'description' => ['text', $this->sanitizeText($event->description)],
                'location' => ['text', $this->formatLocationForIcs($event)],
                'organizer' => ['cal-address', 'mailto:'.self::ORGANIZER_EMAIL],
                'status' => ['text', $event->isCancelled() ? 'CANCELLED' : 'CONFIRMED'],
                'class' => ['text', 'PUBLIC'],
            ],
            [
                // Mobile-optimized alarms
                [
                    'valarm',
                    [
                        'action' => ['text', 'DISPLAY'],
                        'description' => ['text', $this->sanitizeText(self::PRIMARY_ALARM_TEXT)],
                        'trigger' => ['duration', '-PT15M'],
                    ],
                    [],
                ],
                [
                    'valarm',
                    [
                        'action' => ['text', 'DISPLAY'],
                        'description' => ['text', $this->sanitizeText(self::SECONDARY_ALARM_TEXT)],
                        'trigger' => ['duration', '-PT1H'],
                    ],
                    [],
                ],
            ],
        ];
    }

    private function generateStableEventUid(Event $event): string
    {
        // Generate stable UID that doesn't change between requests
        return \sprintf('event-%d-%s@mens-circle.de', $event->getUid(), md5($event->startDate?->format('c') ?? ''));
    }

    private function formatLocationForIcs(Event $event): string
    {
        if ($event->isOnline()) {
            $url = trim($event->callUrl);

            return $url !== '' && $url !== '0' ? "Online Event\\n{$url}" : 'Online Event';
        }

        return $this->sanitizeText($event->getFullAddress());
    }

    private function sanitizeText(string $text): string
    {
        // Remove potential problematic characters for mobile calendar apps
        $text = trim($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text); // Remove control characters

        return str_replace(["\r\n", "\r", "\n"], '\n', $text); // Escape line breaks for iCal
    }

    private function isValidEvent(Event $event): bool
    {
        // Validate event has required fields for mobile compatibility
        return isset($event->title) && ($event->title !== '' && $event->title !== '0')
            && $event->startDate instanceof \DateTime
            && $event->endDate instanceof \DateTime
            && $event->startDate < $event->endDate;
    }
}
