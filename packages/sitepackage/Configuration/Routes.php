<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use MensCircle\Sitepackage\Controller\SubscriptionController;

return [
    'unsubscribe_newsletter' => [
        'path' => '/newsletter/unsubscribe/{token}',
        'target' => SubscriptionController::class.'::unsubscribeAction',
    ],
];
