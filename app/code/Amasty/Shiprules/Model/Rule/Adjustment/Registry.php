<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule\Adjustment;

use Amasty\Shiprules\Model\Rule\AdjustmentData as Adjustment;
use Amasty\Shiprules\Model\Rule\AdjustmentDataFactory as Factory;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Adjustment Keeper.
 */
class Registry
{
    const KEY_SEPARATOR = '~';

    /**
     * @var array
     */
    private $storage = [];

    /**
     * @var Factory
     */
    private $adjustmentFactory;

    public function __construct(Factory $adjustmentFactory)
    {
        $this->adjustmentFactory = $adjustmentFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate $rate
     * @param string $addressHash
     *
     * @return Adjustment|bool
     */
    public function get($rate, $addressHash)
    {
        $rateKey = $this->getRateKey($rate);

        return isset($this->storage[$addressHash][$rateKey]) ? $this->storage[$addressHash][$rateKey] : false;
    }

    /**
     * @param Adjustment $adjustment
     * @param string $addressHash
     *
     * @return Registry
     */
    public function set(Adjustment $adjustment, $addressHash)
    {
        $this->storage[$addressHash][$adjustment->getRateKey()] = $adjustment;

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     * @param string $addressHash
     *
     * @return Adjustment
     * @throws AlreadyExistsException
     */
    public function createForRate($rate, $addressHash)
    {
        $rateKey = $this->getRateKey($rate);

        if ($this->get($rate, $addressHash)) {
            throw new AlreadyExistsException(__('Adjustment for %1 already exists', $rateKey));
        }

        $this->storage[$addressHash][$rateKey] = $this->adjustmentFactory->create();
        $this->storage[$addressHash][$rateKey]->setRateKey($rateKey);

        return $this->storage[$addressHash][$rateKey];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method $rate
     * @param string $addressHash
     */
    public function destroyRate($rate, $addressHash)
    {
        if (isset($this->storage[$addressHash])) {
            $rateKey = $this->getRateKey($rate);
            unset($this->storage[$addressHash][$rateKey]);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateResult\Method|null $rate
     *
     * @return string
     */
    private function getRateKey(\Magento\Quote\Model\Quote\Address\RateResult\Method $rate)
    {
        return $rate->getCarrier() . self::KEY_SEPARATOR . $rate->getMethod();
    }
}
