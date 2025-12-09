<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Domain\Model\Newsletter\Newsletter;
use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;

return [
    FrontendUser::class => [
        'tableName' => 'fe_users',
    ],
    Subscription::class => [
        'tableName' => 'tx_sitepackage_domain_model_subscription',
    ],
    Newsletter::class => [
        'tableName' => 'tx_sitepackage_domain_model_newsletter',
    ],
];
