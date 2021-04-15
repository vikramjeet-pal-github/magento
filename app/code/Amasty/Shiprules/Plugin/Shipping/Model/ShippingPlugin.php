<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Plugin\Shipping\Model;

use Amasty\Shiprules\Api\ShippingRuleApplierInterface as ApplierInterface;

/**
 * Entry point of RuleApplier.
 */
class ShippingPlugin
{
    /**
     * @var \Amasty\Shiprules\Model\Rule\Applier|ApplierInterface
     */
    private $applier;

    /**
     * @var string
     */
    private $currentCarrier = null;

    public function __construct(ApplierInterface $applier)
    {
        $this->applier = $applier;
    }

    public function aroundCollectRates(
        \Magento\Shipping\Model\Shipping $subject,
        callable $proceed,
        \Magento\Quote\Model\Quote\Address\RateRequest $request
    ) {
        /** @var \Magento\Shipping\Model\Shipping $originalMethodResult */
        $originalMethodResult = $proceed($request);

        if (!$this->applier->canApplyAnyRule($request, $originalMethodResult->getResult()->getAllRates())) {
            return $originalMethodResult;
        }

        //Save original result for correct return.
        $originalResult = clone $originalMethodResult->getResult();

        $this->applier->calculateAdjustments($originalResult->getAllRates());

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $rate */
        foreach ($originalResult->getAllRates() as $rate) {
            //Check all rate for `product tab` conditions.
            //If any condition is set, recollect ALL rates.
            if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                continue;
            }
            foreach ($this->applier->getRulesForCarrier($rate) as $rule) {
                if ($newRequest = $this->applier->getModifiedRequest($rate, $request, $rule)) {
                    $subject->getResult()->reset();

                    //Save carrier code to re-calculate only it.
                    $this->currentCarrier = $rate->getCarrier();
                    $proceed($newRequest);
                    $this->currentCarrier = null;

                    //And re-calculate adjustment using original ana new $rate value.
                    $newRate = $this->getNewRate($subject, $rate);
                    $this->applier->calculateRateAdjustment($newRate, $newRequest);
                }
            }
            //And apply changes.
            $this->applier->applyAdjustment($rate);
        }

        $originalMethodResult->getResult()->reset();
        $originalMethodResult->getResult()->append($originalResult);

        return $originalMethodResult;
    }

    private function getNewRate(
        \Magento\Shipping\Model\Shipping $subject,
        \Magento\Quote\Model\Quote\Address\RateResult\Method $oldRate
    ) {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $rate */
        foreach ($subject->getResult()->getRatesByCarrier($oldRate->getCarrier()) as $rate) {
            if ($rate->getCode() === $oldRate->getCode()) {
                return $rate;
            }
        }

        return $oldRate;
    }
}
