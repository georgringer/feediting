<?php

declare(strict_types=1);

namespace GeorgRinger\Feediting\AdminPanel;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Rootline extends AbstractSubModule implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'feediting_rootline';
    }

    public function getLabel(): string
    {
        return 'Rootline';
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:sub.php.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = $request->getAttribute('frontend.controller');
        $currentPage = $frontendController->page;
        $currentPageId = $frontendController->id ?? 0;
        return new ModuleData(
            [
                'rootline' => BackendUtility::BEgetRootLine($currentPageId),
            ]
        );
    }

    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:feediting/Resources/Private/AdminPanel/Templates/Modules/Rootline.html'));
        $view->setPartialRootPaths(['EXT:feediting/Resources/Private/AdminPanel/Partials']);

        $view
            ->assignMultiple($data->getArrayCopy())
            ->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render();
    }
}
