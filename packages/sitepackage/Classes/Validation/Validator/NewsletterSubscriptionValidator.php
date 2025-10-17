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
        if (\in_array(trim($value->firstName), ['', '0'], true)) {
            $this->result->forProperty('firstName')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.firstName.notEmpty',
                    'sitepackage',
                    []
                )
            );
        }

        // Validate last name
        if (\in_array(trim($value->lastName), ['', '0'], true)) {
            $this->result->forProperty('lastName')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.lastName.notEmpty',
                    'sitepackage',
                    []
                )
            );
        }

        // Validate email
        if (\in_array(trim($value->email), ['', '0'], true)) {
            $this->result->forProperty('email')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.email.notEmpty',
                    'sitepackage',
                    []
                )
            );
        } elseif (!filter_var($value->email, \FILTER_VALIDATE_EMAIL)) {
            $this->result->forProperty('email')->addError(
                $this->translateErrorMessage(
                    'validator.newsletter.email.invalid',
                    'sitepackage',
                    []
                )
            );
        }
    }
}
