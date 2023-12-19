<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\ContentObject;

use GeorgRinger\Feediting\Edit\EditPanel;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;

class EditableFluidTemplateContentObject extends FluidTemplateContentObject
{

    public function render($conf = [])
    {
        [$tableName, $recordId] = explode(':', $this->cObj->currentRecord);
        $editPanel = GeneralUtility::makeInstance(EditPanel::class, $this->request, $tableName, (int)$recordId, $this->cObj->data);
        $data = $editPanel->render();
        if (empty($data)) {
            return parent::render($conf);
        }
        // todo class optional
//        if ($tableName === 'pages') {
        $identifier = 'trigger' . md5((string)$data . $tableName . $recordId);
        $content = '
<div class="popover-container">
  <button class="feediting-popover-trigger" data-position="top" data-popover-target="popover-' . $identifier . '">Edit</button>

  <template data-popover="popover-' . $identifier . '">
    ' . $data . '
  </template>
</div>';
//        }
        return '<div class="tx-feediting-fluidtemplate tx-feediting-fluidtemplate-' . $tableName . '">' . parent::render($conf) . $content . '</div>';
    }


}