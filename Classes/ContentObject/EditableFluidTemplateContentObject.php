<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\ContentObject;

use GeorgRinger\Feediting\Edit\EditPanel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;

class EditableFluidTemplateContentObject extends FluidTemplateContentObject
{

    public function render($conf = [])
    {
        $split = explode(':', $this->cObj->currentRecord);
        $tableName = $split[0] ?? '';
        $recordId = (int)($split[1] ?? 0);
        $content = parent::render($conf);

        if (!$tableName || !$recordId) {
            return $content;
        }

        $editPanel = GeneralUtility::makeInstance(EditPanel::class, $this->request, $tableName, (int)$recordId, $this->cObj->data);
        $contentWithEditPanel = $editPanel->render($content);
        if (empty($contentWithEditPanel)) {
            return $content;
        }

        return $contentWithEditPanel;
    }

}