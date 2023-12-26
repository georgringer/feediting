<?php

declare(strict_types=1);


namespace GeorgRinger\Feediting\AdminPanel;

use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EditModule extends AbstractModule implements ShortInfoProviderInterface
{

    public function isEnabled(): bool
    {
        // todo currently hardcoded
        return true;
    }

    public function getIconIdentifier(): string
    {
        return 'feediting-adminpanel-module-icon';
    }

    public function getIdentifier(): string
    {
        return 'feediting';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:feediting/Resources/Private/Language/locallang_adminpanel.xlf:module.label');
    }

    public function getShortInfo(): string
    {
        return '';
    }

}
