<?php

return [
    'frontend' => [
        'typo3/cms-frontendedit/initiator' => [
            'target' => \GeorgRinger\Feediting\Middleware\FrontendEditInitiator::class,
            'after' => [
                'typo3/cms-adminpanel/initiator',
                'typo3/cms-frontend/page-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ]
];
