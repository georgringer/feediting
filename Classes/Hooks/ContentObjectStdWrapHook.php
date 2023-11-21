<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Hooks;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;

class ContentObjectStdWrapHook implements ContentObjectStdWrapHookInterface
{
    public function stdWrapPreProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapOverride($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    public function stdWrapPostProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        if(empty($parentObject->currentRecord)) {
            return $content;
        }
        if (!str_starts_with($parentObject->currentRecord, 'tt_content:')) {
            return $content;
        }
//        DebuggerUtility::var_dump($content);
        return $content;
        return '<div style="border: 1:px solid red;">'.$content . '</div>';
    }

}