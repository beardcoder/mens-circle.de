<?php

declare(strict_types=1);

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
        new Mutation(
            MutationMode::Extend,
            Directive::ScriptSrc,
            SourceScheme::data,
        ),

        new Mutation(
            MutationMode::Extend,
            Directive::ScriptSrc,
            SourceKeyword::unsafeEval,
        ),
    ),
]);
