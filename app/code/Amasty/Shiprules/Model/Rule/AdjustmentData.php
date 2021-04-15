<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule;

/**
 * Adjustment Data Model
 */
class AdjustmentData
{
    const MIN = 'minimal_value';
    const MAX = 'maximal_value';

    /**
     * @var float
     */
    private $value = 0;

    /**
     * @var string
     */
    private $rateKey = '';

    /**
     * @var array
     */
    private $rateTotalValue = [
        self::MIN => null,
        self::MAX => null,
    ];

    /**
     * Flag for rejecting rewrite model data.
     *
     * @var bool
     */
    private $isBlocked = false;

    /**
     * Lock model data.
     *
     * @return void
     */
    public function block()
    {
        $this->isBlocked = true;
    }

    /**
     * @param float $minValue
     * @param float $maxValue
     *
     * @return $this
     */
    public function setRateTotal($minValue, $maxValue)
    {
        if ($this->isBlocked()) {
            return $this;
        }

        $this->rateTotalValue = [
            self::MIN => $this->rateTotalValue[self::MIN] !== null
                ? min($this->rateTotalValue[self::MIN], $minValue) : $minValue,
            self::MAX => $this->rateTotalValue[self::MAX] !== null
                ? max($this->rateTotalValue[self::MAX], $maxValue) : $maxValue,
        ];

        return $this;
    }

    /**
     * @param string $rateKey
     *
     * @return AdjustmentData
     */
    public function setRateKey($rateKey)
    {
        if ($this->isBlocked()) {
            return $this;
        }

        $this->rateKey = $rateKey;

        return $this;
    }

    /**
     * @param float $value
     *
     * @return AdjustmentData
     */
    public function setValue($value)
    {
        if ($this->isBlocked()) {
            return $this;
        }

        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getRateKey()
    {
        return $this->rateKey;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getRateTotalRange()
    {
        return $this->rateTotalValue;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->isBlocked;
    }
}
