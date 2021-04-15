<?php
namespace Vonnda\ShippingRestrictions\Model\Shipping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;

class Restrictions
{

    /** @constant string XML_PATH_GENERAL_COUNTRY_ALLOW_SHIP */
    const XML_PATH_GENERAL_COUNTRY_ALLOW_SHIP = 'general/country/allow_ship';

    /** @property ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /** @property StoreManagerInterface $storeManager */
    protected $storeManager;

    /** @property CountryCollection $countryCollection */
    protected $countryCollection;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CountryCollection $countryCollection
     * @return void
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CountryCollection $countryCollection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->countryCollection = $countryCollection;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllowedShippingCountries()
    {
        $allowedCountries =  $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_COUNTRY_ALLOW_SHIP,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
        return explode(',', $allowedCountries);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllowedShippingCountriesOptionArray()
    {
        $allowedCountries = $this->countryCollection->loadByStore($this->storeManager->getStore());
        $allowedCountries->addFieldToFilter('country_id', ['in' => $this->getAllowedShippingCountries()]);
        return $allowedCountries->toOptionArray();
    }

}