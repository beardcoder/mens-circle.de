/**
 * Validation utilities
 */

/**
 * Email validation regex pattern (RFC 5322 simplified)
 */
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

/**
 * Validates an email address
 * @param email - The email address to validate
 * @returns True if valid, false otherwise
 */
export function validateEmail(email: string): boolean {
  if (!email || typeof email !== 'string') {
    return false
  }

  return EMAIL_PATTERN.test(email.trim())
}

/**
 * Validates a required field (not empty)
 * @param value - The value to validate
 * @returns True if not empty, false otherwise
 */
export function validateRequired(value: string | null | undefined): boolean {
  if (value === null || value === undefined) {
    return false
  }

  return value.toString().trim().length > 0
}

/**
 * Validates minimum length
 * @param value - The value to validate
 * @param minLength - Minimum required length
 * @returns True if meets minimum length, false otherwise
 */
export function validateMinLength(
  value: string | null | undefined,
  minLength: number,
): boolean {
  if (!validateRequired(value)) {
    return false
  }

  return value.toString().trim().length >= minLength
}

/**
 * Validates maximum length
 * @param value - The value to validate
 * @param maxLength - Maximum allowed length
 * @returns True if within maximum length, false otherwise
 */
export function validateMaxLength(
  value: string | null | undefined,
  maxLength: number,
): boolean {
  if (value === null || value === undefined) {
    return true
  }

  return value.toString().trim().length <= maxLength
}
