<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Middleware;

use GeorgRinger\Feediting\Service\AccessService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;

class FrontendEditInitiator implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            $accessService = new AccessService();
            if ($accessService->enabled()) {
                $GLOBALS['TSFE']->set_no_cache('EXT:feediting in action', true);
            }
        }
        return $handler->handle($request);
    }

}
