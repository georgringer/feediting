<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Configuration
{

    protected string $renderCheck = '';

    public function __construct()
    {
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('feediting');

        $renderCheck = strtolower($extensionSettings['renderCheck'] ?? '');
        if ($renderCheck === 'adminpanel' && ExtensionManagementUtility::isLoaded('admin_panel')) {
            $this->renderCheck = 'adminpanel';
        } else {
            $this->renderCheck = $renderCheck;
        }

    }

    public function getRenderCheck(): string
    {
        return $this->renderCheck;
    }



}