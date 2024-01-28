<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\ViewHelpers;

use GeorgRinger\Feediting\Edit\EditPanel;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class PanelViewHelper extends AbstractTagBasedViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'The table to be edited', true);
        $this->registerArgument('uid', 'int', 'Id', true);
    }

    public function render(): string
    {
        $recordId = (int)$this->arguments['uid'];
        $tableName = $this->arguments['table'];
        $row = BackendUtility::getRecord($tableName, $recordId);
        if (!$row) {
            return '';
        }

        $editPanel = GeneralUtility::makeInstance(EditPanel::class, $this->renderingContext->getRequest(), $tableName, $recordId, $row);
        $contentWithEditPanel = $editPanel->render('');
        if (empty($contentWithEditPanel)) {
            return '';
        }
        return $contentWithEditPanel;
    }
}