<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

return Map::fromEntries([
    // Provide declarations for the backend
    Scope::backend(),
    // NOTICE: When using `MutationMode::Set` existing declarations will be overridden

    new MutationCollection(
        new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
        new Mutation(MutationMode::Extend, Directive::FontSrc, SourceScheme::data),

        // Extends the ancestor directive ('default-src'),
        // thus reuses 'self' and adds additional sources
        // Results in `img-src 'self' data: https://*.typo3.org`
        new Mutation(
            MutationMode::Extend,
            Directive::ScriptSrc,
            SourceKeyword::unsafeEval,
            new UriValue('https://cdnjs.cloudflare.com'),
        ),
    ),
]);
