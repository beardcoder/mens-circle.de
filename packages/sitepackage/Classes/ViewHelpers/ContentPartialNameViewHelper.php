<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ContentPartialNameViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;

    protected $escapeOutput = false;

    private const string PREFIX = 'sitepackage';

    private const string FALLBACK_PARTIAL = 'Generic';

    #[\Override]
    public function initializeArguments(): void
    {
        $this->registerArgument(
            name: 'value',
            type: 'string',
            description: 'Content type identifier (e.g., sitepackage_text, sitepackage_image)',
            required: true
        );
    }

    #[\Override]
    public function render(): string
    {
        $value = (string) $this->arguments['value'];

        return $this->extractPartialName($value);
    }

    /**
     * Extract and capitalize the partial name from content type identifier.
     */
    private function extractPartialName(string $contentType): string
    {
        if (!str_starts_with($contentType, self::PREFIX)) {
            return self::FALLBACK_PARTIAL;
        }

        $lastPart = strrchr($contentType, '_');
        if ($lastPart === false) {
            return self::FALLBACK_PARTIAL;
        }

        $partialName = substr($lastPart, 1);
        if ($partialName === '') {
            return self::FALLBACK_PARTIAL;
        }

        return ucfirst($partialName);
    }
}
