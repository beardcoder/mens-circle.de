<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Enum;

enum EventStatusEnum: string
{
    /**
     * The event has been cancelled. If the event has multiple startDate values,
     * all are assumed to be cancelled. Either startDate or previousStartDate
     * may be used to specify the event's cancelled date(s).
     *
     * @see https://schema.org/EventCancelled
     */
    case EventCancelled = 'https://schema.org/EventCancelled';

    /**
     * Indicates that the event was changed to allow online participation. See
     * [[eventAttendanceMode]] for specifics of whether it is now fully or
     * partially online.
     *
     * @see https://schema.org/EventMovedOnline
     */
    case EventMovedOnline = 'https://schema.org/EventMovedOnline';

    /**
     * The event has been postponed and no new date has been set. The event's
     * previousStartDate should be set.
     *
     * @see https://schema.org/EventPostponed
     */
    case EventPostponed = 'https://schema.org/EventPostponed';

    /**
     * The event has been rescheduled. The event's previousStartDate should be
     * set to the old date and the startDate should be set to the event's new
     * date. (If the event has been rescheduled multiple times, the
     * previousStartDate property may be repeated.).
     *
     * @see https://schema.org/EventRescheduled
     */
    case EventRescheduled = 'https://schema.org/EventRescheduled';

    /**
     * The event is taking place or has taken place on the startDate as
     * scheduled. Use of this value is optional, as it is assumed by default.
     *
     * @see https://schema.org/EventScheduled
     */
    case EventScheduled = 'https://schema.org/EventScheduled';
}
