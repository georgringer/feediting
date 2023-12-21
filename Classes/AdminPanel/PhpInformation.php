<?php

declare(strict_types=1);

namespace GeorgRinger\Feediting\AdminPanel;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class PhpInformation extends AbstractSubModule implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'feediting_phpinfo';
    }

    public function getLabel(): string
    {
        return 'phpinfo';
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:sub.php.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData(
            [
                'general' => [
                    'PHP_VERSION' => PHP_VERSION,
                    'PHP_OS' => PHP_OS,
                    'PHP_SAPI' => PHP_SAPI,
                    'Peak Memory Usage' => GeneralUtility::formatSize(memory_get_peak_usage()),
                ],
                'loadedExtensions' => implode(', ', get_loaded_extensions()),
                'constants' => get_defined_constants(true),
            ]
        );
    }

    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Info/PhpInfo.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render();
    }
}
