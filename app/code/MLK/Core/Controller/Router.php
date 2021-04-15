<?php
/**
 * Router.php
 */
declare(strict_types=1);

namespace MLK\Core\Controller;

use Magento\Framework\{
    App\ActionFactory,
    App\Action\Forward,
    App\RequestInterface,
    App\ResponseInterface,
    App\RouterInterface,
    Url\HostChecker
};
use MLK\Core\{
    Controller\Index\Redirect,
    Helper\Data as CoreHelper
};

class Router implements RouterInterface
{
    /** @property ActionFactory $actionFactory */
    protected $actionFactory;

    /** @property CoreHelper $coreHelper */
    protected $coreHelper;

    /** @property HostChecker $hostChecker */
    protected $hostChecker;

    /** @property ResponseInterface $response */
    protected $response;

    /**
     * @param ActionFactory $actionFactory
     * @param CoreHelper $coreHelper
     * @param HostChecker $hostChecker
     * @param ResponseInterface $response
     * @return void
     */
    public function __construct(
        ActionFactory $actionFactory,
        CoreHelper $coreHelper,
        HostChecker $hostChecker,
        ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->coreHelper = $coreHelper;
        $this->hostChecker = $hostChecker;
        $this->response = $response;
    }

    /**
     * @param RequestInterface $request
     * @return Magento\Framework\App\ActionInterface
     */
    public function match(RequestInterface $request)
    {
        /** @var string $identifier */
        $identifier = trim($request->getPathInfo() ?? '', '/');
        $identifier = !empty($identifier) ? $identifier : '/';

        if (!$this->coreHelper->isRedirectEnabled()) {
            return null;
        }

        /** @var string $redirectUrl */
        $redirectUrl = $this->coreHelper->getRedirectUrl();

        /** @var string $routePath */
        $routePath = $this->coreHelper
            ->getAllowedPatternsRoutePath();

        /** @var array $urlParts */
        $urlParts = parse_url($redirectUrl);

        /** @var string $urlPath */
        $urlPath = trim($urlParts['path'] ?? '', '/');
        $urlPath = !empty($urlPath) ? $urlPath : $routePath; /* Fallback for when no path was specified. */

        /**
         * Prevents recursive redirects when the
         * request path and redirect path match.
         */
        if (strpos($identifier, $urlPath) === 0) {
            return null;
        }

        /** @var bool $isRouteAllowed */
        $isRouteAllowed = false;

        /** @var array $patterns */
        $patterns = $this->coreHelper->getAllowedPatterns();

        /** @var string $pattern */
        foreach ($patterns as $pattern) {
            if (strstr($identifier, $pattern) !== false) {
                $isRouteAllowed = true;
            }
        }

        if (!$isRouteAllowed) {
            return $this->actionFactory->create(
                Index\Redirect::class,
                ['request' => $request]
            );
        }

        return null;
    }
}
