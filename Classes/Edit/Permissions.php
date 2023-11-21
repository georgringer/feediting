<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Edit;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class Permissions
{

    public function userLoggedIn(): bool
    {
        $user = $this->getBackendUser();
        if (!$user) {
            return false;
        }
        return true;
    }

    public function editPage(int $pageId)
    {
        $user = $this->getBackendUser();
        if (!$user) {
            return false;
        }
        $row = BackendUtility::getRecord('pages', $pageId);
        return $user->doesUserHaveAccess($row, Permission::PAGE_EDIT);
    }

    public function editElement(string $tableName, array $row): bool
    {
        $user = $this->getBackendUser();
        if (!$user) {
            return false;
        }
//        DebuggerUtility::var_dump($user, $tableName);die;
        $conf = [
            'allow' => 'edit, new, delete, hide, move, localize, versions, permissions, info, history, workspace, recordInfo',
            'onlyCurrentPid' => false,
        ];
        return $user->allowedToEdit($tableName, $row, $conf, true);
    }


    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

}