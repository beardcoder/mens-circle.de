<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Service;

/**
 * Simple iCalendar (RFC 5545) generator.
 *
 * Generates minimal, valid iCal files without external dependencies.
 */
final readonly class ICalGenerator
{
    private const string CRLF = "\r\n";

    public function __construct(
        private string $prodId = '-//Mens Circle//Event Calendar//DE',
    ) {
    }

    /**
     * Generate iCal content for a single event.
     *
     * @param array{
     *     uid: string,
     *     summary: string,
     *     description: string,
     *     start: \DateTimeInterface,
     *     end: \DateTimeInterface,
     *     location?: string,
     *     geo?: array{lat: float, lon: float},
     *     organizer?: array{email: string, name: string}
     * } $event
     */
    public function generate(array $event): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.$this->prodId,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$this->escape($event['uid']),
            'DTSTAMP:'.$this->formatDateTime(new \DateTimeImmutable()),
            'DTSTART:'.$this->formatDateTime($event['start']),
            'DTEND:'.$this->formatDateTime($event['end']),
            'SUMMARY:'.$this->escape($event['summary']),
        ];

        if ($event['description'] !== '') {
            $lines[] = 'DESCRIPTION:'.$this->escape($event['description']);
        }

        if (isset($event['location']) && $event['location'] !== '') {
            $lines[] = 'LOCATION:'.$this->escape($event['location']);
        }

        if (isset($event['geo'])) {
            $lines[] = \sprintf('GEO:%.6f;%.6f', $event['geo']['lat'], $event['geo']['lon']);
        }

        if (isset($event['organizer'])) {
            $lines[] = \sprintf(
                'ORGANIZER;CN=%s:mailto:%s',
                $this->escape($event['organizer']['name']),
                $event['organizer']['email']
            );
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode(self::CRLF, $lines).self::CRLF;
    }

    /**
     * Format DateTime to iCal format (UTC).
     */
    private function formatDateTime(\DateTimeInterface $dateTime): string
    {
        $utc = \DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new \DateTimeZone('UTC'))
        ;

        return $utc->format('Ymd\THis\Z');
    }

    /**
     * Escape special characters per RFC 5545.
     */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', ''],
            $value
        );
    }
}
