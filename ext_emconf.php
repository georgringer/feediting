<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Feediting',
    'description' => '',
    'category' => 'fe',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'backend' => '12.4.0-12.4.99',
            'frontend' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'adminpanel' => '12.4.0-12.4.99',
        ],
    ],
];
