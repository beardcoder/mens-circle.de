<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Lightweight responsive image ViewHelper that generates an <img> tag with srcset.
 *
 * Usage:
 * <app:responsiveImage image="{file}" class="hero__img" sizes="(min-width: 1024px) 50vw, 100vw" />
 */
final class ResponsiveImageViewHelper extends AbstractTagBasedViewHelper
{
    private const array DEFAULT_WIDTHS = [320, 480, 640, 768, 1024, 1280, 1536];

    protected $tagName = 'img';

    protected $escapeChildren = false;

    protected $escapeOutput = false;

    public function __construct(private readonly ImageService $imageService)
    {
        parent::__construct();
    }

    #[\Override]
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('image', 'object', 'FAL FileReference or File object', true);
        $this->registerArgument('widths', 'array|string', 'List of widths for srcset (array or comma-separated string)', false, self::DEFAULT_WIDTHS);
        $this->registerArgument('sizes', 'string', 'sizes attribute that mirrors your layout', false, '100vw');
        $this->registerArgument('crop', 'string|bool|array', 'Override cropping of image (FALSE disables cropping stored in FileReference)', false);
        $this->registerArgument('cropVariant', 'string', 'Cropping variant name', false, 'default');
        $this->registerArgument('format', 'string', 'Force a specific target file extension (e.g. webp)', false, '');
        $this->registerArgument('absolute', 'bool', 'Create absolute URLs', false, false);
        $this->registerArgument('loading', 'string', 'loading attribute value', false, 'lazy');
        $this->registerArgument('decoding', 'string', 'decoding attribute value', false, 'async');
        $this->registerArgument('fetchpriority', 'string', 'fetchpriority attribute value', false, '');
        $this->registerArgument('allowUpScaling', 'bool', 'Allow sizes larger than the original file width', false, false);
    }

    #[\Override]
    public function render(): string
    {
        if ((string) $this->arguments['format'] !== '' && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], (string) $this->arguments['format'])) {
            throw new Exception($this->getExceptionMessage('The extension '.$this->arguments['format'].' is not specified as allowed in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'), 1718029341);
        }

        try {
            $image = $this->imageService->getImage('', $this->arguments['image'], true);
            $originalWidth = (int) ($image->getProperty('width') ?? 0);
            $widths = $this->normalizeWidths($this->arguments['widths'], $originalWidth, (bool) $this->arguments['allowUpScaling']);

            if ($widths === []) {
                return '';
            }

            $cropVariantCollection = CropVariantCollection::create($this->resolveCropString($image, $this->arguments['crop']));
            $cropVariant = (string) ($this->arguments['cropVariant'] ?: 'default');
            $sources = $this->buildSources($image, $widths, $cropVariantCollection, $cropVariant, (string) $this->arguments['format']);

            if ($sources === []) {
                return '';
            }

            $srcSet = implode(', ', array_map(
                static fn (array $source): string => \sprintf('%s %dw', $source['uri'], $source['width']),
                $sources
            ));
            $fallback = array_last($sources);

            $this->tag->addAttribute('src', $fallback['uri']);
            $this->tag->addAttribute('srcset', $srcSet);

            $sizes = trim((string) $this->arguments['sizes']);
            if ($sizes !== '') {
                $this->tag->addAttribute('sizes', $sizes);
            }

            if ($fallback['width'] > 0) {
                $this->tag->addAttribute('width', (string) $fallback['width']);
            }

            if ($fallback['height'] > 0) {
                $this->tag->addAttribute('height', (string) $fallback['height']);
            }

            $this->tag->addAttribute('loading', (string) $this->arguments['loading']);
            $this->tag->addAttribute('decoding', (string) $this->arguments['decoding']);

            if ($this->arguments['fetchpriority'] !== '') {
                $this->tag->addAttribute('fetchpriority', (string) $this->arguments['fetchpriority']);
            }

            $this->ensureAltAttribute($image);
            $this->ensureTitleAttribute($image);

            return $this->tag->render();
        } catch (\Exception $exception) {
            throw new Exception($this->getExceptionMessage($exception->getMessage()), 1718029343, $exception);
        }
    }

    /**
     * @param array<int|string, mixed>|string|null $rawWidths
     *
     * @return int[]
     */
    private function normalizeWidths(array|string|null $rawWidths, int $originalWidth, bool $allowUpScaling): array
    {
        if (\is_string($rawWidths)) {
            $rawWidths = array_filter(array_map(trim(...), explode(',', $rawWidths)), static fn (string $value): bool => $value !== '');
        }

        if (!\is_array($rawWidths) || $rawWidths === []) {
            $rawWidths = self::DEFAULT_WIDTHS;
        }

        $widths = [];

        foreach ($rawWidths as $rawWidth) {
            $width = (int) $rawWidth;
            if ($width <= 0) {
                continue;
            }

            if (!$allowUpScaling && $originalWidth > 0) {
                $width = min($width, $originalWidth);
            }

            $widths[$width] = $width;
        }

        sort($widths, \SORT_NUMERIC);

        return $widths;
    }

    private function resolveCropString(FileInterface $file, string|bool|null $crop): string
    {
        $cropString = $crop;

        if ($cropString === null && $file->hasProperty('crop') && $file->getProperty('crop')) {
            $cropString = $file->getProperty('crop');
        }

        if (\is_array($cropString)) {
            $cropString = (string) json_encode($cropString);
        }

        if ($cropString === false) {
            return '';
        }

        return (string) $cropString;
    }

    /**
     * @param array<int> $widths
     *
     * @return array<int, array{uri: string, width: int, height: int}>
     */
    private function buildSources(
        FileInterface $file,
        array $widths,
        CropVariantCollection $cropVariantCollection,
        string $cropVariant,
        string $format,
    ): array {
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);
        $processingBase = [
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file),
        ];

        if ($format !== '') {
            $processingBase['fileExtension'] = $format;
        }

        $sources = [];

        foreach ($widths as $width) {
            $processingInstructions = $processingBase;
            $processingInstructions['maxWidth'] = $width;

            $processedImage = $this->imageService->applyProcessingInstructions($file, $processingInstructions);
            $sources[] = [
                'uri' => $this->imageService->getImageUri($processedImage, (bool) $this->arguments['absolute']),
                'width' => (int) $processedImage->getProperty('width'),
                'height' => (int) $processedImage->getProperty('height'),
            ];
        }

        return $sources;
    }

    private function ensureAltAttribute(FileInterface $file): void
    {
        if ($this->tag->hasAttribute('alt')) {
            return;
        }

        if (isset($this->additionalArguments['alt']) && $this->additionalArguments['alt'] === '') {
            $this->tag->addAttribute('alt', '');

            return;
        }

        $alternative = $file->hasProperty('alternative') ? (string) $file->getProperty('alternative') : '';
        $this->tag->addAttribute('alt', $alternative);
    }

    private function ensureTitleAttribute(FileInterface $file): void
    {
        if ($this->tag->hasAttribute('title') || !empty($this->additionalArguments['title'] ?? '')) {
            return;
        }

        $title = $file->hasProperty('title') ? (string) $file->getProperty('title') : '';
        if ($title !== '') {
            $this->tag->addAttribute('title', $title);
        }
    }

    private function getExceptionMessage(string $detailedMessage): string
    {
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $currentContentObject = $request->getAttribute('currentContentObject');
            if ($currentContentObject instanceof ContentObjectRenderer) {
                return \sprintf('Unable to render responsive image in "%s": %s', $currentContentObject->currentRecord, $detailedMessage);
            }
        }

        return 'Unable to render responsive image: '.$detailedMessage;
    }
}
