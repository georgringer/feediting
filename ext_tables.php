<?php

// Extend user settings
$GLOBALS['TYPO3_USER_SETTINGS']['columns']['feediting'] = [
    'label' => 'Enable FE Editing',
    'type' => 'check',
];
if (!isset($GLOBALS['TYPO3_USER_SETTINGS']['showitem'])) {
    $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] = '';
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('--div--;EXT:feediting,feediting');

