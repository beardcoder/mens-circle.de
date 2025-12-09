<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Validation\Validator;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use TYPO3\CMS\Extbase\Error\Error;
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
            $this->result->forProperty('firstName')->addError(new Error(
                $this->translateErrorMessage(
                    'validator.newsletter.firstName.notEmpty',
                    'sitepackage',
                    []
                ), 1623423455
            ),
            );
        }

        // Validate last name
        if (\in_array(trim($value->lastName), ['', '0'], true)) {
            $this->result->forProperty('lastName')->addError(
                new Error($this->translateErrorMessage(
                    'validator.newsletter.lastName.notEmpty',
                    'sitepackage',
                    []
                ), 1623423456)
            );
        }

        // Validate email
        if (\in_array(trim($value->email), ['', '0'], true)) {
            $this->result->forProperty('email')->addError(
                new Error($this->translateErrorMessage(
                    'validator.newsletter.email.notEmpty',
                    'sitepackage',
                    []
                ), 1623423457)
            );
        } elseif (!filter_var($value->email, \FILTER_VALIDATE_EMAIL)) {
            $this->result->forProperty('email')->addError(
                new Error(
                    $this->translateErrorMessage(
                        'validator.newsletter.email.invalid',
                        'sitepackage',
                        []
                    ), 1623423458
                )
            );
        }
    }
}
