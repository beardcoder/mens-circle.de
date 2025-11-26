<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Enum;

use Spatie\SchemaOrg\EventAttendanceModeEnumeration;
use Spatie\SchemaOrg\Schema;

enum EventAttendanceModeEnum: int
{
    case OFFLINE = 0;
    case ONLINE = 1;

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function selects(): array
    {
        return array_map(static fn (EventAttendanceModeEnum $eventAttendanceModeEnum): array => [
            'value' => $eventAttendanceModeEnum->value,
            'label' => \sprintf(
                'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.attendance_mode.options.%s',
                strtolower($eventAttendanceModeEnum->name),
            ),
        ], self::cases());
    }

    public function getDescription(): EventAttendanceModeEnumeration
    {
        $value = match ($this) {
            EventAttendanceModeEnum::OFFLINE => EventAttendanceModeEnumeration::OfflineEventAttendanceMode,
            EventAttendanceModeEnum::ONLINE => EventAttendanceModeEnumeration::OnlineEventAttendanceMode,
        };

        return Schema::eventAttendanceModeEnumeration()->setProperty('@id', $value);
    }
}
