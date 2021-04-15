<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule;

use Amasty\CommonRules\Model\OptionProvider\Provider\CalculationOptionProvider;
use Amasty\Shiprules\Api\ShippingRuleApplierInterface;
use Amasty\Shiprules\Model\Rule;

/**
 * Class Applier
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Applier implements ShippingRuleApplierInterface
{
    /**
     * Rules that passed validation for current rate request.
     *
     * @var Rule[]
     */
    private $validRules = [];

    /**
     * Rules that passed validation for current carrier.
     *
     * @var array(Rule[])
     */
    private $rulesByCarrier = [];

    /**
     * Id of all items from current rate request.
     *
     * @var array
     */
    private $allItemsId = [];

    /**
     * @var array(int[])
     */
    private $calculatedItemsByRate;

    /**
     * @var bool
     */
    private $freeShipping = false;

    /**
     * Need to calculate or get valid total value.
     *
     * @var null|string
     */
    private $currentHash = null;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Adjustment\Total
     */
    private $total;

    /**
     * @var Adjustment\Registry
     */
    private $adjustmentRegistry;

    /**
     * @var Adjustment\Calculator
     */
    private $adjustmentCalculator;

    public function __construct(
        Validator $validator,
        Adjustment\Total $total,
        Adjustment\Registry $adjustmentRegistry,
        Adjustment\Calculator $adjustmentCalculator
    ) {
        $this->validator = $validator;
        $this->total = $total;
        $this->adjustmentRegistry = $adjustmentRegistry;
        $this->adjustmentCalculator = $adjustmentCalculator;
    }

    /**
     * @inheritDoc
     */
    public function applyAdjustment(\Magento\Quote\Model\Quote\Address\RateResult\Method $rate)
    {
        $adjustment = $this->adjustmentRegistry->get($rate, $this->currentHash);

        $rate->setOldPrice($rate->getPrice());
        $ratePrice = $rate->getPrice() + $adjustment->getValue();

        $range = $adjustment->getRateTotalRange();
        $ratePrice = max((float)$range[AdjustmentData::MIN], $ratePrice);

        if ((float)$range[AdjustmentData::MAX]) {
            $ratePrice = min((float)$range[AdjustmentData::MAX], $ratePrice);
        }

        $rate->setPrice(max($ratePrice, 0));
    }

    /**
     * @inheritDoc
     */
    public function getModifiedRequest(
        \Magento\Quote\Model\Quote\Address\RateResult\Method $rate,
        \Magento\Quote\Model\Quote\Address\RateRequest $request,
        \Amasty\Shiprules\Model\Rule $rule
    ) {
        if (!count($specifiedProducts = $this->getSpecifiedProductsByRate($rate, $rule))) {
            return false;
        }

        $itemsIds = [];
        foreach ($specifiedProducts as $item) {
            $itemsIds[] = $item->getId();
        }
        $rateCode = $rate->getCarrier() . '_' . $rate->getMethod();

        if (isset($this->calculatedItemsByRate[$rateCode])
            && array_intersect($itemsIds, $this->calculatedItemsByRate[$rateCode])
        ) {
            return false;
        }

        $this->calculatedItemsByRate[$rateCode] = $itemsIds;
        $newRequest = clone $request;

        $this->total->calculate(
            $specifiedProducts,
            $this->currentHash . \spl_object_hash($rate),
            $this->freeShipping
        );

        $newRequest->setLimitCarrier($rate->getCarrier());
        $newRequest->setLimitMethod($rate->getMethod());

        $newRequest->setAllItems($specifiedProducts);
        $newRequest->setPackageValue($this->total->getPrice());
        $newRequest->setPackageWeight($this->total->getWeight());
        $newRequest->setPackageQty($this->total->getQty());
        $newRequest->setFreeMethodWeight($this->total->getNotFreeWeight());

        $newRequest->setPackageValueWithDiscount($newRequest->getPackageValue());
        $newRequest->setPackagePhysicalValue($newRequest->getPackageValue());

        return $newRequest;
    }

    /**
     * Reset stored data
     */
    public function reset()
    {
        $this->calculatedItemsByRate = [];
        $this->rulesByCarrier = [];
        $this->validator->reset();
    }

    /**
     * @inheritDoc
     */
    public function calculateAdjustments($ratesArray)
    {
        foreach ($ratesArray as $rate) {
            if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                continue;
            }
            if (!($adjustment = $this->adjustmentRegistry->get($rate, $this->currentHash))
                || $adjustment->isBlocked()
            ) {
                continue;
            }

            $adjustmentValue = 0;

            foreach ($this->getRulesForCarrier($rate) as $rule) {
                if (array_diff($this->allItemsId, array_keys($this->validator->getValidItems($rule)))) {
                    continue;
                }

                $adjustmentValue += $this->adjustmentCalculator->calculateByRule(
                    $rule,
                    $rate,
                    $this->currentHash,
                    $this->freeShipping
                );
            }

            $adjustment->setValue($adjustment->getValue() + $adjustmentValue);
        }
    }

    /**
     * @inheritDoc
     */
    public function calculateRateAdjustment(\Magento\Quote\Model\Quote\Address\RateResult\Method $rate, $newRequest)
    {
        if (!($adjustment = $this->adjustmentRegistry->get($rate, $this->currentHash)) || $adjustment->isBlocked()) {
            return;
        }

        $ids = [];
        foreach ($newRequest->getAllItems() as $item) {
            $ids[] = $item->getId();
        }

        $adjustmentValue = 0;

        foreach ($this->getRulesForCarrier($rate) as $rule) {
            $ruleItemsIds = array_keys($this->validator->getValidItems($rule));
            if (!array_intersect($ids, $ruleItemsIds) || !array_diff($this->allItemsId, $ruleItemsIds)) {
                continue;
            }

            $adjustmentValue += $this->adjustmentCalculator->calculateByRule(
                $rule,
                $rate,
                $this->currentHash,
                $this->freeShipping
            );
        }

        $adjustment->setValue($adjustment->getValue() + $adjustmentValue);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function canApplyAnyRule(\Magento\Quote\Model\Quote\Address\RateRequest $request, $rates)
    {
        $this->reset();
        if (!($this->validRules = $this->validator->getValidRules($request, $this->total))) {
            return false;
        }

        $this->currentHash = $this->validator->getAddressHash($request);
        $this->allItemsId = $this->validator->collectAllItemsId($request->getAllItems());
        $this->freeShipping = $request->getFreeShipping();

        foreach ($rates as $rate) {
            if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                continue;
            }
            if ($this->adjustmentRegistry->get($rate, $this->currentHash)) {
                $this->adjustmentRegistry->destroyRate($rate, $this->currentHash);
            }
            $adjustment = $this->adjustmentRegistry->createForRate($rate, $this->currentHash);

            foreach ($this->validRules as $rule) {
                if ($rule->match($rate)) {
                    $this->registerRuleForRate($rule, $rate);
                    $adjustment->setRateTotal($rule->getShipMin(), $rule->getShipMax());

                    if ($rule->getCalc() == CalculationOptionProvider::CALC_REPLACE) {
                        $adjustment->setValue(
                            $this->adjustmentCalculator->calculateByRule(
                                $rule,
                                $rate,
                                $this->currentHash,
                                $this->freeShipping
                            )
                        )->block();

                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     * @param Rule $rule
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    private function getSpecifiedProductsByRate(
        \Magento\Quote\Model\Quote\Address\RateResult\Method $rate,
        Rule $rule
    ) {
        $specifiedProducts = [];

        if ($rule->match($rate)
            && array_diff($this->allItemsId, array_keys($this->validator->getValidItems($rule)))
        ) {
            $specifiedProducts = $this->validator->getValidItems($rule);
        }

        return $specifiedProducts;
    }

    /**
     * @param Rule $rule
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     *
     * @return bool
     */
    private function registerRuleForRate($rule, $rate)
    {
        $rateCode = $rate->getCarrier() . '_' . $rate->getMethod();
        if (!isset($this->rulesByCarrier[$rateCode])) {
            $this->rulesByCarrier[$rateCode][] = $rule;
            return true;
        }

        $isMixedCartRule = $rule->getCalc() == CalculationOptionProvider::CALC_REPLACE_PRODUCT;

        foreach ($this->rulesByCarrier[$rateCode] as $registeredRule) {
            if ($registeredRule->getId() === $rule->getId()
                || ($isMixedCartRule && $registeredRule->getCalc() == CalculationOptionProvider::CALC_REPLACE_PRODUCT)
            ) {
                return false;
            }
        }

        $this->rulesByCarrier[$rateCode][] = $rule;

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     *
     * @return Rule[]
     */
    public function getRulesForCarrier($rate)
    {
        $rateCode = $rate->getCarrier() . '_' . $rate->getMethod();
        if (!isset($this->rulesByCarrier[$rateCode])) {
            return [];
        }

        return $this->rulesByCarrier[$rateCode];
    }

    /**
     * @inheritDoc
     */
    public function getValidRules()
    {
        return $this->validRules;
    }
}
