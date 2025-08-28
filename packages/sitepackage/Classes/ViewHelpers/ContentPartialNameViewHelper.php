<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ContentPartialNameViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;

    protected $escapeOutput = false;

    #[\Override]
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'Name of the content type like sitepackage_text or sitepackage_image', true);
    }

    #[\Override]
    public function render(): string
    {
        $value = (string) $this->arguments['value'];
        if (str_starts_with($value, 'sitepackage')) {
            return ucfirst(substr(strrchr($value, '_'), 1));
        }

        return 'Generic';
    }
}
