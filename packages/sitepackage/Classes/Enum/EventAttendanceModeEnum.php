<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Enum;

use Spatie\SchemaOrg\EventAttendanceModeEnumeration;

enum EventAttendanceModeEnum: int
{
    case OFFLINE = 0;
    case ONLINE = 1;

    public static function selects(): array
    {
        return array_map(static fn ($case): array => [
            'value' => $case->value,
            'label' => \sprintf(
                'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.attendance_mode.options.%s',
                strtolower($case->name),
            ),
        ], self::cases());
    }

    public function getDescription(): EventAttendanceModeEnumeration|string
    {
        return match ($this) {
            EventAttendanceModeEnum::OFFLINE => EventAttendanceModeEnumeration::OfflineEventAttendanceMode,
            EventAttendanceModeEnum::ONLINE => EventAttendanceModeEnumeration::OnlineEventAttendanceMode,
        };
    }
}
