/**
 * Type definitions for Männerkreis Straubing application
 */

export interface EventData {
  title: string;
  description: string;
  location: string;
  startDate: string;
  startTime: string;
  endDate: string;
  endTime: string;
}

export interface RouteConfig {
  newsletter: string;
  eventRegister: string;
  csrfToken: string;
}

export interface FormSubmitResponse {
  success: boolean;
  message: string;
}

export interface NewsletterData {
  email: string;
}

export interface EventRegistrationData {
  event_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone_number: string | null;
  privacy: number;
}

export interface TestimonialData {
  quote: string;
  author_name: string | null;
  role: string | null;
  email: string;
  privacy: number;
}

export type MessageType = 'success' | 'error';

// Global window extensions
declare global {
  interface Window {
    eventData?: EventData;
    routes: RouteConfig;
  }
}
