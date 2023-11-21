<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['feediting'] = \GeorgRinger\Feediting\Hooks\ContentObjectStdWrapHook::class;

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adminpanel')) {

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['mynamespace_modulename'] = [
        'module' => \GeorgRinger\Feediting\AdminPanel\EditModule::class,
//        'before' => ['cache'],
        'submodules' => [
            'general' => [
                'module' => \GeorgRinger\Feediting\AdminPanel\PhpInformation::class,
            ],

        ],
    ];
}