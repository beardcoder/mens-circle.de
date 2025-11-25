<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug;

class EventSlugService
{
    /**
     * @param array<string, mixed> $params
     */
    public function modifySlug(array $params): string
    {
        $raw = (string) ($params['record']['start_date'] ?? '');
        $timestamp = $raw !== '' ? strtotime($raw) : false;
        if ($timestamp === false) {
            return '';
        }

        return date('d-m-Y', $timestamp);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getPrefix(array $parameters, TcaSlug $tcaSlug): string
    {
        return '';
    }
}
