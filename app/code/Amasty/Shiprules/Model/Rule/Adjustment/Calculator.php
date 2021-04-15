<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule\Adjustment;

use Amasty\CommonRules\Model\OptionProvider\Provider\CalculationOptionProvider;
use Amasty\Shiprules\Model\Rule;
use Magento\Quote\Model\Quote\Address\RateResult\Method as Rate;

/**
 * Adjustment Calculator.
 */
class Calculator
{
    /**
     * @var Total
     */
    private $total;

    /**
     * @var array
     */
    private $ratePrice = [];

    public function __construct(
        Total $total
    ) {
        $this->total = $total;
    }

    /**
     * @param Rule $rule
     * @param Rate $rate
     * @param $hash
     * @param bool $isFree
     *
     * @return float
     */
    public function calculateByRule(Rule $rule, Rate $rate, $hash, $isFree)
    {
        $hash = $hash . $rule->getId();
        $rateHash = $hash . \spl_object_hash($rate);

        if (isset($this->ratePrice[$rateHash])) {
            return $this->ratePrice[$rateHash];
        }

        if ($isFree && !$rule->getIgnorePromo()) {
            return $this->ratePrice[$rateHash] = 0;
        }

        $this->total->setHash($hash);
        $rateValue = 0;

        if ($rule->getIgnorePromo()) {
            $price = $this->total->getPrice();
            $qty = $this->total->getQty();
            $weight = $this->total->getWeight();
        } else {
            $price = $this->total->getNotFreePrice();
            $qty = $this->total->getNotFreeQty();
            $weight = $this->total->getNotFreeWeight();
        }

        if ($qty > 0) {
            $rateValue = $rule->getRateBase();
        }

        $rateValue += $qty * $rule->getRateFixed();
        $rateValue += $price * $rule->getRatePercent() / 100;
        $rateValue += $weight * $rule->getWeightFixed();
        $rateValue += $rate->getPrice() * $rule->getHandling() / 100;

        $rateValue = $this->checkChangeBoundary($rateValue, $rule);

        switch ($rule->getCalc()) {
            case CalculationOptionProvider::CALC_REPLACE:
            case CalculationOptionProvider::CALC_REPLACE_PRODUCT:
                $rateValue = $rateValue - $rate->getPrice();
                break;
            case CalculationOptionProvider::CALC_DEDUCT:
                $rateValue = 0 - $rateValue;
                break;
        }

        return $this->ratePrice[$rateHash] = $rateValue;
    }

    /**
     * @param float $rateValue
     * @param Rule $rule
     *
     * @return bool
     */
    private function checkChangeBoundary($rateValue, $rule)
    {
        if ((float)$rule->getRateMax() && abs($rateValue) > $rule->getRateMax()) {
            $rateValue = $rule->getRateMax();
        }
        if (abs($rateValue) < $rule->getRateMin()) {
            $rateValue = $rule->getRateMin();
        }

        return $rateValue;
    }
}
