<?php

declare(strict_types=1);

namespace GeorgRinger\Feediting\AdminPanel;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = $request->getAttribute('frontend.controller');
        $currentPage = $frontendController->page;
        $currentPageId = $frontendController->id ?? 0;
        $availableLanguages = $request->getAttribute('site')->getAvailableLanguages($this->getBackendUser(), false, $currentPageId);
        unset($availableLanguages[0]);
        $pageTranslations = $this->getExistingPageTranslations($currentPageId);
        $languagesToTranslateTo = $translatedPages = [];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($availableLanguages as $availableLanguage) {
            $languageId = $availableLanguage->getLanguageId();
            if (!isset($pageTranslations[$languageId]) && $languageId > 0) {
                $targetUrl = (string)$uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            'pages' => [
                                $currentPageId => [
                                    'localize' => $languageId,
                                ],
                            ],
                        ],
                        'redirect' => (string)$uriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'justLocalized' => 'pages:' . $currentPageId . ':' . $languageId,
                                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                            ]
                        ),
                    ]
                );
                $languagesToTranslateTo[$languageId] = [
                    'title' => $availableLanguages[$languageId]->getTitle(),
                    'url' => $targetUrl,
                ];
            } else {
                $translatedPages[] = [
                    'language' =>  $availableLanguage,
                    'page' => $this->getLocalizedPageRecord($availableLanguage->getLanguageId(), $currentPageId)
                ];
            }
        }
//DebuggerUtility::var_dump($translatedPages);die;
        return new ModuleData(
            [
                'languagesToTranslateTo' => $languagesToTranslateTo,
                'translatedPages' => $translatedPages,
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

    protected function getExistingPageTranslations(int $pageId): array
    {
        if ($pageId === 0) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $rows = $queryBuilder
            ->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($rows as $row) {
            $translations[$row['sys_language_uid']] = $row;
        }
        return $translations;
    }

    protected function getLocalizedPageRecord(int $languageId, $pageId): ?array
    {
        if ($languageId === 0) {
            return null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $overlayRecord = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($overlayRecord) {
            BackendUtility::workspaceOL('pages', $overlayRecord, $this->getBackendUser()->workspace);
        }
        return is_array($overlayRecord) ? $overlayRecord : null;
    }


}
