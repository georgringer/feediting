<?php

declare(strict_types=1);

namespace GeorgRinger\Feediting\AdminPanel;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class Information extends AbstractSubModule implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'feediting_phpinfo';
    }

    public function getLabel(): string
    {
        return 'Info';
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:sub.php.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData(
            [
            ]
        );
    }

    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:feediting/Resources/Private/AdminPanel/Templates/Modules/Info.html'));
        $view->setPartialRootPaths(['EXT:feediting/Resources/Private/AdminPanel/Partials']);

        $view
            ->assignMultiple($data->getArrayCopy())
            ->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render();
    }
}
