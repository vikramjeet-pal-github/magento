<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Model\Quote;

use Magento\Quote\Model\Quote\Address as CoreAddress;

class Address extends CoreAddress
{
    /**
     * Collecting shipping rates by address
     *
     * @return $this
     */
    public function collectShippingRatesWithOverride($priceOverride, $storeManagerInterface)
    {
        if (!$this->getCollectShippingRates()) {
            return $this;
        }

        $this->setCollectShippingRates(false);

        $this->removeAllShippingRates();

        if (!$this->getCountryId()) {
            return $this;
        }

        $found = $this->requestShippingRatesWithOverride(null, $priceOverride, $storeManagerInterface);
        if (!$found) {
            $this->setShippingAmount(0)->setBaseShippingAmount(0)->setShippingMethod('')->setShippingDescription('');
        }

        return $this;
    }

    /**
     * Request shipping rates for entire address or specified address item
     * Returns true if current selected shipping method code corresponds to one of the found rates
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function requestShippingRatesWithOverride($item, $priceOverride, $storeManagerInterface)
    {
        $this->storeManager = $storeManagerInterface;
        
        /** @var $request \Magento\Quote\Model\Quote\Address\RateRequest */
        $request = $this->_rateRequestFactory->create();
        $request->setAllItems($item ? [$item] : $this->getAllItems());
        $request->setDestCountryId($this->getCountryId());
        $request->setDestRegionId($this->getRegionId());
        $request->setDestRegionCode($this->getRegionCode());
        $request->setDestStreet($this->getStreetFull());
        $request->setDestCity($this->getCity());
        $request->setDestPostcode($this->getPostcode());
        $request->setPackageValue($item ? $item->getBaseRowTotal() : $this->getBaseSubtotal());
        $packageWithDiscount = $item ? $item->getBaseRowTotal() -
            $item->getBaseDiscountAmount() : $this->getBaseSubtotalWithDiscount();
        $request->setPackageValueWithDiscount($packageWithDiscount);
        $request->setPackageWeight($item ? $item->getRowWeight() : $this->getWeight());
        $request->setPackageQty($item ? $item->getQty() : $this->getItemQty());

        /**
         * Need for shipping methods that use insurance based on price of physical products
         */
        $packagePhysicalValue = $item ? $item->getBaseRowTotal() : $this->getBaseSubtotal() -
            $this->getBaseVirtualAmount();
        $request->setPackagePhysicalValue($packagePhysicalValue);

        $request->setFreeMethodWeight($item ? 0 : $this->getFreeMethodWeight());

        /**
         * Store and website identifiers specified from StoreManager
         */
        $request->setStoreId($this->storeManager->getStore()->getId());
        $request->setWebsiteId($this->storeManager->getWebsite()->getId());
        $request->setFreeShipping($this->getFreeShipping());
        /**
         * Currencies need to convert in free shipping
         */
        $request->setBaseCurrency($this->storeManager->getStore()->getBaseCurrency());
        $request->setPackageCurrency($this->storeManager->getStore()->getCurrentCurrency());
        $request->setLimitCarrier($this->getLimitCarrier());
        $baseSubtotalInclTax = $this->getBaseSubtotalTotalInclTax();
        $request->setBaseSubtotalInclTax($baseSubtotalInclTax);

        $result = $this->_rateCollector->create()->collectRates($request)->getResult();

        $found = false;
        if ($result) {
            $shippingRates = $result->getAllRates();

            foreach ($shippingRates as $shippingRate) {
                $rate = $this->_addressRateFactory->create()->importShippingRate($shippingRate);
                if($priceOverride || $this->isZeroNumber($priceOverride)){
                    $rate->setPrice($priceOverride);
                }
                if (!$item) {
                    $this->addShippingRate($rate);
                }

                if ($this->getShippingMethod() == $rate->getCode()) {
                    if ($item) {
                        $item->setBaseShippingAmount($rate->getPrice());
                    } else {
                        /** @var \Magento\Store\Api\Data\StoreInterface */
                        $store = $this->storeManager->getStore();
                        $amountPrice = $store->getBaseCurrency()
                            ->convert($rate->getPrice(), $store->getCurrentCurrencyCode());
                        $this->setBaseShippingAmount($rate->getPrice());
                        $this->setShippingAmount($amountPrice);
                    }

                    $found = true;
                }
            }
        }

        return $found;
    }

    public function isZeroNumber($field)
    {
        if($field === false){
            return false;
        }

        if($field === ""){
            return false;
        }

        if($field === null){
            return false;
        }

        $num = floatval($field);
        if($num == 0){
            return true;
        }

        return false;
    }
}
