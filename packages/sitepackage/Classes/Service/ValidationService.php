<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

final class ValidationService
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $rules
     *
     * @return array<string, string>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($field, $value, $rule);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    private function validateRule(string $field, mixed $value, string $rule): ?string
    {
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        return match ($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, (int)$parameter),
            'max' => $this->validateMax($field, $value, (int)$parameter),
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value): ?string
    {
        if (in_array($value, [null, '', []], true)) {
            return sprintf('The %s field is required.', $field);
        }

        return null;
    }

    private function validateEmail(string $field, mixed $value): ?string
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return sprintf('The %s field must be a valid email address.', $field);
        }

        return null;
    }

    private function validateMin(string $field, mixed $value, int $min): ?string
    {
        if (!is_string($value) || mb_strlen($value) < $min) {
            return sprintf('The %s field must be at least %d characters.', $field, $min);
        }

        return null;
    }

    private function validateMax(string $field, mixed $value, int $max): ?string
    {
        if (!is_string($value) || mb_strlen($value) > $max) {
            return sprintf('The %s field must not exceed %d characters.', $field, $max);
        }

        return null;
    }
}
