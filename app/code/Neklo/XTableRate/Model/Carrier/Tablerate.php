<?php
/*
NOTICE OF LICENSE

This source file is subject to the NekloEULA that is bundled with this package in the file ICENSE.txt.

It is also available through the world-wide-web at this URL: http://store.neklo.com/LICENSE.txt

Copyright (c)  Neklo (http://store.neklo.com/)
*/


namespace Neklo\XTableRate\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory as OfflineTablerateFactory;
use Neklo\XTableRate\Model\ResourceModel\Carrier\TablerateFactory as NekloTablerateFactory;

class Tablerate extends \Magento\OfflineShipping\Model\Carrier\Tablerate
{
    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    private $productUrl;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        RateResult\MethodFactory $resultMethodFactory,
        OfflineTablerateFactory $trf,
        NekloTablerateFactory $tablerateFactory,
        \Magento\Catalog\Model\Product\Url $productUrl,
        $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateResultFactory,
            $resultMethodFactory,
            $trf,
            $data
        );
        $this->_tablerateFactory = $tablerateFactory;
        $this->productUrl = $productUrl;
    }

    /**
     * @param RateRequest $request
     *
     * @return \Magento\Shipping\Model\Rate\Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price')
            && $request->getAllItems()
        ) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        $freePackageValue = 0;
        $freeWeight = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                            $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                            $freeWeight += $item->getWeight();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                    $freeQty += $item->getQty() - $freeShipping;
                    $freePackageValue += $item->getBaseRowTotal();
                    $freeWeight += $item->getWeight();
                }
            }

            $oldValue = $request->getPackageValue();
            $request->setPackageValue($oldValue - $freePackageValue);
        }

        if (!$request->getConditionName()) {
            $conditionName = $this->getConfigData('condition_name');
            $request->setConditionName($conditionName ? $conditionName : $this->_defaultConditionName);
        }

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        $request->setPackageWeight($request->getPackageWeight() - $freeWeight);
        $request->setPackageQty($oldQty - $freeQty);

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();
        $rates = $this->getRate($request);
        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

        if ($request->getFreeShipping() ==  true || $request->getPackageQty() == $freeQty) {
            /**
             * was applied promotion rule for whole cart
             * other shipping methods could be switched off at all
             * we must show table rate method with 0$ price, if grand_total
             * more, than min table condition_value
             * free setPackageWeight() has already was taken into account
             */
            $request->setPackageValue($freePackageValue);
            $request->setPackageQty($freeQty);

            $method = $this->_resultMethodFactory->create();
            $method->setCarrier('tablerate');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('bestway');
            $method->setMethodTitle($this->getConfigData('free_shipping_name'));

            $method->setPrice(0);
            $method->setCost(0);

            $result->append($method);

            return $result;
        }

        if (is_array($rates) && count($rates) >= 0) {
            // add shipping methods
            foreach ($rates as $rate) {
                if ($request->getDestCountryId() == $rate['dest_country_id'] || $rate['dest_country_id'] == '0') {
                    $method = $this->_resultMethodFactory->create();
                    $method->setCarrier('tablerate');
                    $method->setCarrierTitle($this->getConfigData('title'));
                    $method->setMethod($this->getMethodCodeByName($rate['shipping_name']));
                    $method->setMethodTitle($rate['shipping_name']);
                    $method->setPrice($rate['price']);
                    $method->setCost($rate['cost']);
                    $result->append($method);
                }
            }
        } else {
            $error = $this->_rateErrorFactory->create(
                [
                    'data' => [
                        'carrier'       => $this->_code,
                        'carrier_title' => $this->getConfigData('title'),
                        'error_message' => $this->getConfigData('specificerrmsg'),
                    ]
                ]
            );
            $result->append($error);
        }

        return $result;
    }

    public function getMethodCodeByName($shippingName)
    {
        return substr($this->productUrl->formatUrlKey($shippingName), 0, 100);
    }
}
