<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug;

class EventSlugService
{
    public function modifySlug(array $params): string
    {
        $raw = (string) ($params['record']['start_date'] ?? '');
        $timestamp = $raw !== '' ? strtotime($raw) : false;
        if ($timestamp === false) {
            return '';
        }

        return date('d-m-Y', $timestamp);
    }

    public function getPrefix(array $parameters, TcaSlug $tcaSlug): string
    {
        return '';
    }
}
