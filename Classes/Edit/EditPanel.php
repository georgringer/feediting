<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Edit;

use GeorgRinger\Feediting\Service\AccessService;
use GeorgRinger\Feediting\Utility\ContentElementOrder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class EditPanel
{

    protected Permissions $permissions;
    protected IconFactory $iconFactory;
    protected string $moduleName;
    protected UriBuilder $uriBuilder;
    protected int $permissionsOfPage;
    protected array $pageRow;
    protected AccessService $accessService;
    protected bool $enabled = false;

    public function __construct(
        protected ServerRequestInterface $request,
        protected string $tableName,
        protected int $recordId,
        protected array $row
    )
    {
        if (!$this->getBackendUser()) {
            return;
        }
        $this->accessService = GeneralUtility::makeInstance(AccessService::class);
        if (!$this->accessService->enabled()) {
            return;
        }

        $this->enabled = true;

        $this->permissions = GeneralUtility::makeInstance(Permissions::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

//        $moduleName = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['newContentElementWizard.']['override'] ?? 'new_content_element_wizard';
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
//        $perms = $this->getBackendUser()->calcPerms($tsfe->page);
        $this->pageRow = $this->getTypoScriptFrontendController()->page;
        $this->permissionsOfPage = $this->getBackendUser()->calcPerms($this->pageRow);
    }

    public function render(string $content): string
    {
        if (!$this->enabled) {
            return '';
        }
        $isPageTable = $this->tableName === 'pages';
        $allowed = $isPageTable ? $this->permissions->editPage($this->row['pid']) : $this->permissions->editElement($this->tableName, $this->row);
        if (!$allowed) {
            return '';
        }

        $data = $this->collectActions();
        return $this->renderPanel($content, $data);
    }

    protected function history(array &$data)
    {
        // History
        $link = (string)$this->uriBuilder->buildUriFromRoute(
            'record_history',
            [
                'element' => $this->tableName . ':' . $this->recordId,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $data[] = '<a data-fslightbox="history" href="' . htmlspecialchars($link) . '">' . $this->getLinkLabel('history', 'actions-document-history-open') . '</a>';
    }

    protected function edit(array &$data)
    {
        $link = (string)$this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit[' . $this->tableName . '][' . $this->recordId . ']' => 'edit',
                'noView' => 1,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $label = $this->getLinkLabel('edit', 'actions-page-open');
        $data[] = '<a href="' . $link . '">' . $label . '</a>';
    }

    protected function info(array &$data): void
    {
        $link = (string)$this->uriBuilder->buildUriFromRoute(
            'show_item',
            [
                'table' => $this->tableName,
                'uid' => $this->recordId,
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            ]
        );
        $label = $this->getLinkLabel('info', 'actions-document-info');
        $data[] = '<a href="' . htmlspecialchars($link) . '">' . $label . '</a>';
    }

    protected function move(array &$data): void
    {
        if ($this->tableName === 'pages') {
            $link = (string)$this->uriBuilder->buildUriFromRoute(
                'move_page',
                [
                    'uid' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
        } else {
            $link = (string)$this->uriBuilder->buildUriFromRoute(
                'move_element',
                [
                    'table' => $this->tableName,
                    'uid' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
        }
        $label = $this->getLinkLabel('move', 'actions-document-move');
        $data[] = '<a href="' . $link . '">' . $label . '</a>';
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
            $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);

            $label = $this->getLinkLabel('move ' . $direction, 'actions-move-' . $direction);
            $data[] = '<a href="' . $url . '">' . $label . '</a>';
        }
    }

    protected function newPage(array &$data)
    {
        if ($this->permissionsOfPage & Permission::PAGE_NEW) {
            $link = (string)$this->uriBuilder->buildUriFromRoute(
                'db_new',
                [
                    'id' => $this->recordId,
                    'pagesOnly' => 1,
                    'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                ]
            );
            $data[] = '<a href="' . htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) . '">' . $this->getLinkLabel('new page', 'actions-page-new') . '</a>';
        }
    }

    protected function newContent(array &$data)
    {
        if (!($this->permissionsOfPage & Permission::CONTENT_EDIT)) {
            return;
        }
        $backendLayoutview = GeneralUtility::makeInstance(BackendLayoutView::class);
        $layout = $backendLayoutview->getSelectedBackendLayout($this->pageRow['uid']);
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
                        $this->pageRow['uid'] => 'new',
                    ],
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
                'defVals' => [
                    'tt_content' => array_replace($defVals, [
                        'colPos' => $item['value'],
                        'sys_language_uid' => $this->pageRow['sys_language_uid'],
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
        $link = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $this->tableName => [
                    $targetPageRow['uid'] => 'new',
                ],
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'defVals' => [],
        ]);
        $data[] = '<a href="' . htmlspecialchars($link) . '"> ' . $this->getLinkLabel('new record ' . $this->tableName, 'actions-add') . '</a>';

    }

    protected function clipboard(array &$data)
    {
        $isSel = 'xxx';
        $copyUrl = $this->clipboard->selUrlDB($this->tableName, $this->recordId, true, true);
        $icon = $isSel === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy';
        $data[] = '
                <a class="btn btn-default" href="' . htmlspecialchars($copyUrl) . '">
                    ' . $this->getLinkLabel($icon, $icon) . '
                </a>';
    }

    protected function linkToListView(array &$data)
    {
        // Open list view
        if ($this->getBackendUser()->check('modules', 'web_list')) {
            $link = (string)$this->uriBuilder->buildUriFromRoute(
                'web_list',
                [
                    'id' => $this->recordId,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            );
            $data[] = '<a href="' . htmlspecialchars($link) . '">' . $this->getLinkLabel('link to list', 'actions-system-list-open') . '</a>';
        }
    }

    protected function getLinkLabel(string $text, string $identifier)
    {
        $icon = $this->iconFactory->getIcon($identifier, Icon::SIZE_SMALL)->render();

        return sprintf('<span>%s %s</span>', $icon, $text);
    }

    protected function addStyles()
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assetCollector->addStylesheet('feediting', 'EXT:feediting/Resources/Public/Styles/basic.css');
        $assetCollector->addJavaScript('feediting', 'EXT:feediting/Resources/Public/JavaScript/popover.js');
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return array
     */
    protected function collectActions(): array
    {
        $data = [];

        $this->edit($data);
        $this->move($data);
        if ($this->tableName === 'tt_content') {
            $this->moveUpDown($data);
        }
        if ($this->tableName === 'pages') {
            $this->newPage($data);
            $this->linkToListView($data);
            $this->newContent($data);
        } elseif ($this->tableName !== 'tt_content') {
            $this->newRecord($data);
        }
        $this->history($data);
        $this->info($data);
        return $data;
    }

    protected function renderPanel(string $content, array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $this->addStyles();
        array_walk($data, static function (string &$value) {
            $value = '<span class="tx-feediting-element">' . $value . '</span>';
        });
        $inner = '<div class="tx-feediting-panel"><span class="tx-feediting-type">' . $this->tableName . ':<span>' . $this->recordId . '</span></span>' . implode(LF, $data) . '</div>';

        $identifier = 'trigger' . md5($inner);
        $contentCombined = '
<div class="popover-container">
  <button class="feediting-popover-trigger" data-position="top" data-popover-target="popover-' . $identifier . '">Edit</button>

  <template data-popover="popover-' . $identifier . '">
    ' . $inner . '
  </template>
</div>';
        return '<div class="tx-feediting-fluidtemplate tx-feediting-fluidtemplate-' . $this->tableName . '">' . $content . $contentCombined . '</div>';
    }

}
