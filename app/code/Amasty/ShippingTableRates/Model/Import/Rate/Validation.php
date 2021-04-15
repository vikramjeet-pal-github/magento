<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShippingTableRates
 */


namespace Amasty\ShippingTableRates\Model\Import\Rate;

use Amasty\ShippingTableRates\Helper\Data as HelperData;
use Amasty\ShippingTableRates\Model\Import\Rate\Renderer as Renderer;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;

class Validation
{
    /**
     * @var HelperData
     */
    private $helper;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    public function __construct(
        HelperData $helper,
        Renderer $renderer,
        RegionCollectionFactory $regionCollectionFactory
    ) {
        $this->renderer = $renderer;
        $this->helper = $helper;
        $this->regionCollectionFactory = $regionCollectionFactory;
    }

    /**
     * @param null|string $country
     *
     * @return bool
     */
    public function validateCountry($country)
    {
        if (!$country || $country == 'All') {
            return true;
        }

        $countryNames = $this->helper->getCountriesHash();

        if (!array_key_exists($country, $countryNames) && !in_array($country, $countryNames)) {
            return false;
        }

        return true;
    }

    /**
     * @param null|string|int $state
     * @param null|string|int $country
     *
     * @return bool
     */
    public function validateState($state, $country)
    {
        if (!$state || $state == 'All') {
            return true;
        }

        $country = $this->renderer->renderCountry($country);
        $stateNames = $this->helper->getStatesHash();
        $statesData = explode("/", $state);
        $state = count($statesData) > 1 ? $statesData[1] : $statesData[0] ;

        if ($country && $country !== Mapping::COUNTRY_CODE_ALL) {
            $regionsCollection = $this->regionCollectionFactory->create();
            $regionsCollection
                ->addFieldToFilter(['main_table.region_id','main_table.default_name'], [$state, $state])
                ->addFieldToFilter('country_id', $country);
            
            return (bool) $regionsCollection->getSize();
        }

        if (!array_key_exists($state, $stateNames) && !in_array($state, $stateNames)) {
            return false;
        }

        return true;
    }

    /**
     * @param null|string|int $shippingType
     *
     * @return bool
     */
    public function validateShippingType($shippingType)
    {
        if (!$shippingType || $shippingType == 'All') {
            return true;
        }

        $typeLabels = $this->helper->getTypesHash();

        if (empty($typeLabels[$shippingType]) && !in_array($shippingType, $typeLabels)) {
            return false;
        }

        return true;
    }

    /**
     * @param null|string|float $value
     *
     * @return bool
     */
    public function validateNumericValue($value)
    {
        if ($value === null || (is_numeric($value) && $value >= 0)) {
            return true;
        }

        return false;
    }
}
