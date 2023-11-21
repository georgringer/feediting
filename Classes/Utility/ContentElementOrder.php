<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementOrder
{

    protected array $currentTable = [
        'prev' => [],
        'next' => [],
        'prevUid' => [],
    ];

    public function getList(int $pid, int $colpos, int $language = 0)
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryResult = $queryBuilder->select('uid', 'header', 'sorting')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter($colpos, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)),
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery();

        $prevUid = 0;
        $prevPrevUid = 0;
        // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
//        $row = $queryResult->fetchAssociative();
//        $prevPrevUid = -((int)$row['uid']);
        $row = $queryResult->fetchAssociative();

        $prevUid = $row['uid'];
        $backendUser = $GLOBALS['BE_USER'];
        // Accumulate rows here
        while ($row = $queryResult->fetchAssociative()) {
            // In offline workspace, look for alternative record
            BackendUtility::workspaceOL('tt_content', $row, $backendUser->workspace, true);
            if (is_array($row)) {
                if ($prevUid) {
                    $this->currentTable['prev'][$row['uid']] = $prevPrevUid;
                    $this->currentTable['next'][$prevUid] = '-' . $row['uid'];
                    $this->currentTable['prevUid'][$row['uid']] = $prevUid;
                }
                $prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
                $prevUid = $row['uid'];
            }
        }

        return $this->currentTable;
    }

}