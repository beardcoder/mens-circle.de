<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\EventRegistration;

return [
    Event::class => [
        'tableName' => 'tx_sitepackage_domain_model_event',
        'properties' => [
            'eventDate' => [
                'fieldName' => 'event_date',
            ],
            'startTime' => [
                'fieldName' => 'start_time',
            ],
            'endTime' => [
                'fieldName' => 'end_time',
            ],
            'postalCode' => [
                'fieldName' => 'postal_code',
            ],
            'locationDetails' => [
                'fieldName' => 'location_details',
            ],
            'maxParticipants' => [
                'fieldName' => 'max_participants',
            ],
            'costBasis' => [
                'fieldName' => 'cost_basis',
            ],
            'isPublished' => [
                'fieldName' => 'is_published',
            ],
        ],
    ],
    EventRegistration::class => [
        'tableName' => 'tx_sitepackage_domain_model_eventregistration',
        'properties' => [
            'firstName' => [
                'fieldName' => 'first_name',
            ],
            'lastName' => [
                'fieldName' => 'last_name',
            ],
            'phoneNumber' => [
                'fieldName' => 'phone_number',
            ],
            'privacyAccepted' => [
                'fieldName' => 'privacy_accepted',
            ],
            'confirmedAt' => [
                'fieldName' => 'confirmed_at',
            ],
        ],
    ],
];
