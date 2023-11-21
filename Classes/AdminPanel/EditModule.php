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
        return 'actions-document-info';
    }

    public function getIdentifier(): string
    {
        return 'feediting_xxxx';
    }

    public function getLabel(): string
    {
        return 'EXT:feediting';
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:module.label'
        );
    }

    public function getShortInfo(): string
    {
        return 'this is a short info';
        $phpInformation = $this->moduleData->offsetGet($this->subModules['info_php']);
        $parseTime = $this->getTimeTracker()->getParseTime();
        return sprintf(
            $this->getLanguageService()->sL(
                'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:module.shortinfoLoadAndMemory'
            ),
            $parseTime,
            $phpInformation['general']['Peak Memory Usage']
        );
    }


}
