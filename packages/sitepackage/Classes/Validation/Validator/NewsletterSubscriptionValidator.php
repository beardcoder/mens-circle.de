<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Validation\Validator;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class NewsletterSubscriptionValidator extends AbstractValidator
{
    protected function isValid(mixed $value): void
    {
        if (!$value instanceof Subscription) {
            return;
        }

        // Validate first name
        if (empty(trim($value->firstName))) {
            $this->result->forProperty('firstName')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.firstName.notEmpty',
                    'sitepackage',
                    [],
                    'Pflichtfeld'
                ),
                1234567890
            );
        }

        // Validate last name
        if (empty(trim($value->lastName))) {
            $this->result->forProperty('lastName')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.lastName.notEmpty',
                    'sitepackage',
                    [],
                    'Pflichtfeld'
                ),
                1234567891
            );
        }

        // Validate email
        if (empty(trim($value->email))) {
            $this->result->forProperty('email')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.email.notEmpty',
                    'sitepackage',
                    [],
                    'Pflichtfeld'
                ),
                1234567892
            );
        } elseif (!filter_var($value->email, FILTER_VALIDATE_EMAIL)) {
            $this->result->forProperty('email')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.email.invalid',
                    'sitepackage',
                    [],
                    'Ungültige E-Mail'
                ),
                1234567893
            );
        }
    }
}

