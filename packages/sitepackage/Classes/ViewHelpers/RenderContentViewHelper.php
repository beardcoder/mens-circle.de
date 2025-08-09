<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\ViewHelpers;

use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class RenderContentViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments()
    {
        $this->registerArgument('data', 'mixed', 'Record or data array', true);
        $this->registerArgument('type', 'string', 'Content type');
    }

    #[\Override]
    public function render()
    {
        $data = $this->arguments['data'];
        $type = $this->arguments['type'];
        /** @var FluidViewAdapter $view */
        $view = $this->renderingContext->getViewHelperVariableContainer()->getView();

        $renderingContext = $view->getRenderingContext();

        if (!isset($record->content_type)) {
            return '';
        }

        $partialName = 'Content/' . ucfirst(substr(strrchr($record->content_type, '_'), 1));


        return $this->renderPartial($partialName, ['data' => $record]);
    }

}
