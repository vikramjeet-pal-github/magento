<?php
namespace Vonnda\ShippingRestrictions\Plugin\Customer;

use Magento\Customer\Model\Config\Share;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class AllowedCountries
{

    /** @var Share */
    private $shareConfig;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @property ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /**
     * @param Share $share
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Share $share,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shareConfig = $share;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve all allowed countries or specific by scope depends on customer share setting
     * @param \Magento\Directory\Model\AllowedCountries $subject
     * @param string $scope
     * @param string|null $scopeCode
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetAllowedCountries(\Magento\Directory\Model\AllowedCountries $subject, $scope = ScopeInterface::SCOPE_WEBSITE, $scopeCode = null)
    {
        if ($this->shareConfig->isGlobalScope() && $this->scopeConfig->getValue('general/country/country_merge')) {
            $scopeCode = array_map(function (WebsiteInterface $website) {
                return $website->getId();
            }, $this->storeManager->getWebsites());
            $scope = ScopeInterface::SCOPE_WEBSITES;
        }
        return [$scope, $scopeCode];
    }

}