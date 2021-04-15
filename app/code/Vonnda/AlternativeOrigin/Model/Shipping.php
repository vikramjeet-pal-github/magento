<?php

namespace Vonnda\AlternativeOrigin\Model;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Sales\Model\Order\Shipment;
use Vonnda\AlternativeOrigin\Api\Data\AlternativeShippingOriginZonesInterface;
use Vonnda\AlternativeOrigin\Helper\Data;
use Vonnda\AlternativeOrigin\Model\ResourceModel\AlternativeShippingOriginZones\CollectionFactory;

class Shipping extends \Magento\Shipping\Model\Shipping
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CollectionFactory
     */
    protected $alternativeShippingOriginCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Shipping\Model\Shipment\RequestFactory $shipmentRequestFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Math\Division $mathDivision,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        Data $helperData,
        Session $checkoutSession,
        CollectionFactory $alternativeShippingOriginCollectionFactory,
        RateRequestFactory $rateRequestFactory = null
    ) {
        parent::__construct(
            $scopeConfig,
            $shippingConfig,
            $storeManager,
            $carrierFactory,
            $rateResultFactory,
            $shipmentRequestFactory,
            $regionFactory,
            $mathDivision,
            $stockRegistry,
            $rateRequestFactory
        );

        $this->helperData = $helperData;
        $this->checkoutSession = $checkoutSession;
        $this->alternativeShippingOriginCollectionFactory = $alternativeShippingOriginCollectionFactory;
    }

    /**
     * Collect rates of given carrier
     *
     * @param string $carrierCode
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectCarrierRates($carrierCode, $request)
    {
        /* @var $carrier \Magento\Shipping\Model\Carrier\AbstractCarrier */
        $carrier = $this->_carrierFactory->createIfActive($carrierCode, $request->getStoreId());
        if (!$carrier) {
            return $this;
        }
        $carrier->setActiveFlag($this->_availabilityConfigField);
        $result = $carrier->checkAvailableShipCountries($request);
        if (false !== $result && !$result instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
            $result = $carrier->processAdditionalValidation($request);
        }
        // Overwrite to add alternative origin to all rate request is shipping address matches
        $storeId = $request->getStoreId();
        if ($this->helperData->isEnabled($storeId)
        ) {
            $quoteShippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();

            $alternativeShippingOriginCollection = $this->alternativeShippingOriginCollectionFactory->create();
            $alternativeShippingOriginCollection->addFieldToFilter(
                [
                    AlternativeShippingOriginZonesInterface::COUNTRY_ID,
                    AlternativeShippingOriginZonesInterface::REGION_ID,
                    AlternativeShippingOriginZonesInterface::POSTCODE,
                ],
                [
                    ['eq' => $quoteShippingAddress->getCountryId()],
                    ['eq' => $quoteShippingAddress->getRegionId()],
                    ['eq' => $quoteShippingAddress->getPostcode()]
                ]
            );

            $matchOrigin = false;

            /** @var AlternativeShippingOriginZonesInterface $alternativeShippingOrigin */
            foreach ($alternativeShippingOriginCollection as $alternativeShippingOrigin) {

                if ($alternativeShippingOrigin->getCountryId() == $quoteShippingAddress->getCountryId()) {
                    if (empty($alternativeShippingOrigin->getRegionId())) {
                        $matchOrigin = true;
                        break;
                    } elseif ($alternativeShippingOrigin->getRegionId() == $quoteShippingAddress->getRegionId()) {

                        if (empty($alternativeShippingOrigin->getPostcode()) ||
                            $alternativeShippingOrigin->getPostcode() == $quoteShippingAddress->getPostcode()
                        ) {
                            $matchOrigin = true;
                            break;
                        }
                    }
                }
            }

            if ($alternativeShippingOriginCollection->count() > 0 && $matchOrigin) {
                $countryId = $this->helperData->getConfigValue(Data::XML_PATH_STORE_COUNTRY_ID, $storeId);
                $regionId = $this->helperData->getConfigValue(Data::XML_PATH_STORE_REGION_ID, $storeId);
                $city = $this->helperData->getConfigValue(Data::XML_PATH_STORE_CITY, $storeId);
                $zipCode = $this->helperData->getConfigValue(Data::XML_PATH_STORE_ZIP, $storeId);
                $request->setOrigCountry($countryId);
                $request->setOrigRegionId($regionId);
                $request->setOrigCity($city);
                $request->setOrigPostcode($zipCode);        
            }

        }

        /*
         * Result will be false if the admin set not to show the shipping module
         * if the delivery country is not within specific countries
         */
        if (false !== $result) {
            if (!$result instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                if ($carrier->getConfigData('shipment_requesttype')) {
                    $packages = $this->composePackagesForCarrier($carrier, $request);
                    if (!empty($packages)) {
                        $sumResults = [];
                        foreach ($packages as $weight => $packageCount) {
                            $request->setPackageWeight($weight);
                            $result = $carrier->collectRates($request);
                            if (!$result) {
                                return $this;
                            } else {
                                $result->updateRatePrice($packageCount);
                            }
                            $sumResults[] = $result;
                        }
                        if (!empty($sumResults) && count($sumResults) > 1) {
                            $result = [];
                            foreach ($sumResults as $res) {
                                if (empty($result)) {
                                    $result = $res;
                                    continue;
                                }
                                foreach ($res->getAllRates() as $method) {
                                    foreach ($result->getAllRates() as $resultMethod) {
                                        if ($method->getMethod() == $resultMethod->getMethod()) {
                                            $resultMethod->setPrice($method->getPrice() + $resultMethod->getPrice());
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $result = $carrier->collectRates($request);
                    }
                } else {
                    $result = $carrier->collectRates($request);
                }
                if (!$result) {
                    return $this;
                }
            }
            if ($carrier->getConfigData('showmethod') == 0 && $result->getError()) {
                return $this;
            }
            // sort rates by price
            if (method_exists($result, 'sortRatesByPrice') && is_callable([$result, 'sortRatesByPrice'])) {
                $result->sortRatesByPrice();
            }
            $this->getResult()->append($result);
        }
        return $this;
    }
}
