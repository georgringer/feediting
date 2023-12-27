<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\EventListener;

use GeorgRinger\Feediting\Event\EditPanelActionEvent;
use GeorgRinger\Feediting\Utility\ContentElementOrder;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class DefaultEditPanelActionEventListener
{

    protected UriBuilder $uriBuilder;
    protected IconFactory $iconFactory;
    protected string $tableName;
    protected int $recordId;
    protected array $row;
    protected TypoScriptFrontendController $tsfe;

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function __invoke(EditPanelActionEvent $event)
    {
        $this->tableName = $event->table;
        $this->recordId = $event->id;
        $this->row = $event->row;
        $this->tsfe = $event->request->getAttribute('frontend.controller');
        $actions = $event->getActions();

        $this->edit($actions);
        $this->move($actions);
        if ($this->tableName === 'tt_content') {
            $this->moveUpDown($actions);
        }
        if ($this->tableName === 'pages') {
            $this->newPage($actions);
            $this->linkToListView($actions);
            $this->newContent($actions);
        } elseif ($this->tableName !== 'tt_content') {
            $this->newRecord($actions);
        }
        $this->history($actions);
        $this->info($actions);


        $event->setActions($actions);
    }

    protected function edit(array &$data)
    {
        $link = $this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit[' . $this->tableName . '][' . $this->recordId . ']' => 'edit',
                'noView' => 1,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $data[] = $this->generateLink($link, 'actions-page-open', 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit');
    }

    protected function move(array &$data): void
    {
        if ($this->tableName === 'pages') {
            $link = $this->uriBuilder->buildUriFromRoute(
                'move_page',
                [
                    'uid' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
        } else {
            $link = $this->uriBuilder->buildUriFromRoute(
                'move_element',
                [
                    'table' => $this->tableName,
                    'uid' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
        }
        $data[] = $this->generateLink($link, 'actions-document-move', 'LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:move', true);
    }

    protected function moveUpDown(array &$data): void
    {
        $order = GeneralUtility::makeInstance(ContentElementOrder::class);
        $list = $order->getList($this->row['pid'], $this->row['colPos'], $this->row['sys_language_uid']);
        foreach (['up', 'down'] as $direction) {
            $checkKey = $direction === 'up' ? 'prev' : 'next';
            if (!isset($list[$checkKey][$this->row['uid']])) {
                continue;
            }
            $params = [];
            $params['redirect'] = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
            $params['returnUrl'] = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
            $params['cmd'][$this->tableName][$this->recordId]['move'] = $list[$checkKey][$this->row['uid']];
            $url = $this->uriBuilder->buildUriFromRoute('tce_db', $params);

            $data[] = $this->generateLink($url, 'actions-move-' . $direction, 'LLL:EXT:core/Resources/Private/Language/locallang_tsfe.xlf:p_move' . ucfirst($direction));
        }
    }

    protected function history(array &$data)
    {
        // History
        $link = $this->uriBuilder->buildUriFromRoute(
            'record_history',
            [
                'element' => $this->tableName . ':' . $this->recordId,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $data[] = $this->generateLink($link, 'actions-document-history-open', 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.history', true);
    }

    protected function info(array &$data): void
    {
        $link = $this->uriBuilder->buildUriFromRoute(
            'show_item',
            [
                'table' => $this->tableName,
                'uid' => $this->recordId,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $data[] = $this->generateLink($link, 'actions-document-info', 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info');
    }


    protected function newPage(array &$data): void
    {
        if ($this->permissionsOfPage & Permission::PAGE_NEW) {
            $link = $this->uriBuilder->buildUriFromRoute(
                'db_new',
                [
                    'id' => $this->recordId,
                    'pagesOnly' => 1,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
            $data[] = $this->generateLink($link, 'actions-page-new', 'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage2');
        }
    }

    protected function newContent(array &$data)
    {
        if (!($this->permissionsOfPage & Permission::CONTENT_EDIT)) {
            return;
        }
        $backendLayoutview = GeneralUtility::makeInstance(BackendLayoutView::class);
        $layout = $backendLayoutview->getSelectedBackendLayout($this->tsfe->page['uid']);
        $items = [];
        if ($layout && !empty($layout['__items'])) {
            $items = $layout['__items'];
        }

        if (empty($items)) {
            return;
        }
        // todo set ctype, ...?
        // todo which language?

        $defVals = [];
        $links = [];
        $identifier = 'dropdown-' . md5($this->tableName . $this->recordId);
        foreach ($items as $item) {
            $link = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'tt_content' => [
                        $this->tsfe->page['uid'] => 'new',
                    ],
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                'defVals' => [
                    'tt_content' => array_replace($defVals, [
                        'colPos' => $item['value'],
                        'sys_language_uid' => $this->tsfe->page['sys_language_uid'],
                    ]),
                ],
            ]);
            $links[] = sprintf('<li><a href="%s">%s</a></li>', htmlspecialchars($link), $item['label']);
        }
        if (!empty($links)) {
            $data[] = '<div class="tx-feediting-dropdown">
                    <input type="checkbox" id="' . $identifier . '" value="" name="my-checkbox">
                    <label for="' . $identifier . '"
                    data-toggle="dropdown">
                    Choose one
                    </label>
                    <ul>
                    ' . implode(LF, $links) . '
                    </ul>
                    </div>';
        }
    }

    protected function newRecord(array &$data)
    {
        $targetPageRow = BackendUtility::getRecord('pages', $this->row['pid']);
        $permissionsOfTargetPage = $this->getBackendUser()->calcPerms($targetPageRow);
        if (!($permissionsOfTargetPage & Permission::CONTENT_EDIT)) {
            return;
        }
        $link = $this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $this->tableName => [
                    $targetPageRow['uid'] => 'new',
                ],
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'defVals' => [],
        ]);
        $tableTitle = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']) ?: $GLOBALS['TCA'][$this->tableName]['ctrl']['title'] ?: $this->tableName;
        $targetPageTitle = BackendUtility::getRecordTitle('pages', $targetPageRow);
        $label = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewRecord'), $tableTitle, $targetPageTitle);
        $data[] = $this->generateLink($link, 'actions-add', $label);
    }

    protected function linkToListView(array &$data)
    {
        // Open list view
        if ($this->getBackendUser()->check('modules', 'web_list')) {
            $link = $this->uriBuilder->buildUriFromRoute(
                'web_list',
                [
                    'id' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            );
            $data[] = $this->generateLink($link, 'actions-system-list-open', 'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:goToListModule');
        }
    }


    protected function clipboard(array &$data)
    {
        $isSel = 'xxx';
        $copyUrl = $this->clipboard->selUrlDB($this->tableName, $this->recordId, true, true);
        $icon = $isSel === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy';
        $data[] = '
                <a class="btn btn-default" href="' . htmlspecialchars($copyUrl) . '">
                ' . $icon . '
                </a>';
    }


    protected function generateLink(UriInterface $link, string $iconIdentifier, string $linkLabel, bool $lightbox = false): string
    {
        $icon = $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
        $label = $this->getLanguageService()->sL($linkLabel) ?: $linkLabel;
        $label = sprintf('<span>%s %s</span>', $icon, htmlspecialchars($label));
        $classList = [];
        if ($lightbox) {
            $classList[] = 'tx-feediting-lightbox';
        }
        return '<a class="' . implode(' ', $classList) . '" href="' . htmlspecialchars((string)$link, ENT_QUOTES | ENT_HTML5) . '">' . $label . '</a>';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

}