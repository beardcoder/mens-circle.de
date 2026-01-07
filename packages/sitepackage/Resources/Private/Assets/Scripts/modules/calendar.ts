/**
 * Calendar Integration Module
 * Handles "Add to Calendar" functionality for events
 */

import type { EventData } from '../types';

/**
 * Gets event data from data attributes or falls back to window object
 * @returns EventData object
 */
function getEventData(): EventData {
  const button = document.getElementById('addToCalendar');

  if (button) {
    const title = button.dataset.eventTitle;
    const description = button.dataset.eventDescription;
    const location = button.dataset.eventLocation;
    const startDate = button.dataset.eventStartDate;
    const startTime = button.dataset.eventStartTime;
    const endDate = button.dataset.eventEndDate;
    const endTime = button.dataset.eventEndTime;

    if (title && description && location && startDate && startTime && endDate && endTime) {
      return {
        title,
        description,
        location,
        startDate,
        startTime,
        endDate,
        endTime,
      };
    }
  }

  // Fallback to window object for backwards compatibility
  if (typeof window !== 'undefined' && 'eventData' in window) {
    const data = window.eventData as EventData;

    return data;
  }

  // Default fallback
  return {
    title: 'Männerkreis Straubing',
    description: 'Treffen des Männerkreis Straubing. Ein Raum für echte Begegnung unter Männern.',
    location: 'Straubing (genaue Adresse nach Anmeldung)',
    startDate: '2025-01-24',
    startTime: '19:00',
    endDate: '2025-01-24',
    endTime: '21:30',
  };
}

export function initCalendarIntegration(): void {
  const addToCalendarBtn = document.getElementById('addToCalendar');
  const calendarModal = document.getElementById('calendarModal');
  const calendarICS = document.getElementById('calendarICS') as HTMLAnchorElement | null;
  const calendarGoogle = document.getElementById('calendarGoogle') as HTMLAnchorElement | null;

  if (!addToCalendarBtn) return;

  addToCalendarBtn.addEventListener('click', () => {
    if (!calendarModal) {
      return;
    }
    calendarModal.classList.add('open');

    const eventData = getEventData();

    // Generate ICS file
    if (calendarICS) {
      const icsContent = generateICS(eventData);
      const blob = new Blob([icsContent], {
        type: 'text/calendar;charset=utf-8',
      });

      calendarICS.href = URL.createObjectURL(blob);
    }

    // Generate Google Calendar link
    if (calendarGoogle) {
      calendarGoogle.href = generateGoogleCalendarUrl(eventData);
    }
  });

  // Close modal when clicking outside
  if (calendarModal) {
    calendarModal.addEventListener('click', (e: MouseEvent) => {
      if (e.target === calendarModal) {
        calendarModal.classList.remove('open');
      }
    });
  }
}

function generateICS(event: EventData): string {
  const formatDate = (date: string, time: string): string => {
    const d = new Date(`${date}T${time}:00`);

    return d
      .toISOString()
      .replace(/[-:]/g, '')
      .replace(/\.\d{3}/, '');
  };

  const start = formatDate(event.startDate, event.startTime);
  const end = formatDate(event.endDate, event.endTime);
  const now = new Date()
    .toISOString()
    .replace(/[-:]/g, '')
    .replace(/\.\d{3}/, '');

  return `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Männerkreis Straubing//DE
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${start}
DTEND:${end}
DTSTAMP:${now}
UID:${Date.now()}@maennerkreis-straubing.de
SUMMARY:${event.title}
DESCRIPTION:${event.description.replace(/\n/g, '\\n')}
LOCATION:${event.location}
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR`;
}

function generateGoogleCalendarUrl(event: EventData): string {
  const formatGoogleDate = (date: string, time: string): string => {
    return `${date.replace(/-/g, '')}T${time.replace(':', '')}00`;
  };

  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: event.title,
    dates: `${formatGoogleDate(event.startDate, event.startTime)}/${formatGoogleDate(event.endDate, event.endTime)}`,
    details: event.description,
    location: event.location,
    ctz: 'Europe/Berlin',
  });

  return `https://calendar.google.com/calendar/render?${params.toString()}`;
}
