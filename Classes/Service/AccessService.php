<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Service;

use GeorgRinger\Feediting\Configuration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;

class AccessService
{

    protected Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function enabled(): bool
    {
        $beUser = $this->getBackendUser();
        if (!$beUser) {
            return false;
        }

        switch ($this->configuration->getRenderCheck()) {
            case 'usersettings':
                return (bool)($beUser->uc['feediting'] ?? false);
            case 'adminpanel':
                $adminPanelRequestId = $this->getRequest()->getAttribute('adminPanelRequestId');
                return is_string($adminPanelRequestId);
        }

        return false;
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}