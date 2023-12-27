<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Edit;

use GeorgRinger\Feediting\Event\EditPanelActionEvent;
use GeorgRinger\Feediting\Service\AccessService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class EditPanel
{

    protected Permissions $permissions;
    protected string $moduleName;
    protected int $permissionsOfPage;
    protected AccessService $accessService;
    protected bool $enabled = false;
    protected EventDispatcherInterface $eventDispatcher;

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
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->permissions = GeneralUtility::makeInstance(Permissions::class);

//        $moduleName = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['newContentElementWizard.']['override'] ?? 'new_content_element_wizard';
//        $perms = $this->getBackendUser()->calcPerms($tsfe->page);
        $pageRow = $request->getAttribute('frontend.controller')->page;
        $this->permissionsOfPage = $this->getBackendUser()->calcPerms($pageRow);
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

    protected function collectActions(): array
    {
        $data = [];

        $event = $this->eventDispatcher->dispatch(
            new EditPanelActionEvent(
                $this->request,
                $this->tableName,
                $this->recordId,
                $this->row, $data),

        );
        return $event->getActions();
    }

    protected function renderPanel(string $content, array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assetCollector->addStylesheet('feediting', 'EXT:feediting/Resources/Public/Styles/basic.css');
        $assetCollector->addJavaScript('feediting', 'EXT:feediting/Resources/Public/JavaScript/popover.js');

        array_walk($data, static function (string &$value) {
            $value = '<span class="tx-feediting-element">' . $value . '</span>';
        });

        $info = implode(LF, [
            BackendUtility::getRecordTitle($this->tableName, $this->row),
            BackendUtility::getProcessedValue($this->tableName, 'CType', $this->row['CType']),
        ]);

        $identifier = 'trigger' . md5(json_encode($data));
        $elementInformation = '<div class="tx-feediting-type">' . htmlspecialchars($info) . '[<span>' . $this->recordId . '</span>]</div>';
        $panel = '
<div class="popover-container">
  <button class="feediting-popover-trigger" data-position="top" data-popover-target="popover-' . $identifier . '">Edit</button>

  <template data-popover="popover-' . $identifier . '">
    <div class="tx-feediting-panel">'
            . $elementInformation
            . '<div class="tx-feediting-actions">' . implode(LF, $data) . '</div>'
            . '</div>
  </template>
</div>';
        return '<div class="tx-feediting-fluidtemplate tx-feediting-fluidtemplate-' . $this->tableName . '">' . $panel . $content . '</div>';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
