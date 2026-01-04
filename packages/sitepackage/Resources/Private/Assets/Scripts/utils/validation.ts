/**
 * Validation utilities
 */

/**
 * Validates an email address
 * @param email - The email address to validate
 * @returns True if valid, false otherwise
 */
export function validateEmail(email: string): boolean {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}
