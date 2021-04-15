<?php
/**
 * Data.php
 */
declare(strict_types=1);

namespace MLK\Core\Helper;

use Magento\Framework\{
    App\Helper\AbstractHelper,
    App\Helper\Context
};
use Magento\Store\{
    Model\ScopeInterface,
    Model\Store,
    Model\StoreManagerInterface
};

class Data extends AbstractHelper
{
    /** @constant string XML_PATH_MLK_CORE_REDIRECT */
    const XML_PATH_MLK_CORE_REDIRECT = 'mlkcore/redirect/';

    /** @property StoreManagerInterface $storeManager */
    protected $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @return void
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * @param int|null $store
     * @return bool
     */
    public function isRedirectEnabled(int $store = null): bool
    {
        /* Set active store, if not given. */
        $store = $store
            ?? (int) $this->storeManager->getStore()->getId();

        /** @var string|null $redirectUrl */
        $redirectUrl = $this->getRedirectUrl($store) ?? null;

        return (bool)($redirectUrl !== null && !!$this->getConfigValue('redirect_enabled', $store));
    }

    /**
     * @param int|null $store
     * @return string
     */
    public function getRedirectUrl(int $store = null): string
    {
        /* Set active store, if not given. */
        $store = $store
            ?? (int) $this->storeManager->getStore()->getId();

        return $this->getConfigValue('redirect_url', $store);
    }

    /**
     * @param int|null $store
     * @return array
     */
    public function getAllowedPatterns(int $store = null): array
    {
        /* Set active store, if not given. */
        $store = $store
            ?? (int) $this->storeManager->getStore()->getId();

        return explode(',', $this->getConfigValue('allowed_patterns', $store));
    }

    /**
     * @param int|null $store
     * @return string
     */
    public function getAllowedPatternsRoutePath(int $store = null): string
    {
        /* Set active store, if not given. */
        $store = $store
            ?? (int) $this->storeManager->getStore()->getId();

        /** @var array $patterns */
        $patterns = $this->getAllowedPatterns($store) ?? [];

        return trim(
            implode('/', $patterns),
            '/'
        );
    }

    /**
     * @param string $field
     * @param int $store
     * @param string $scope
     */
    protected function getConfigValue(
        string $field,
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ) {
        /** @var string $path */
        $path = self::XML_PATH_MLK_CORE_REDIRECT . $field;

        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $store
        );
    }
}
