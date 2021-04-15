<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */


namespace Amasty\Shiprules\Model\Rule\Adjustment;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;

/**
 * Total data model.
 */
class Total
{
    /**
     * @var null|string
     */
    private $hash = null;

    /**
     * @var array
     */
    private $price = [];

    /**
     * @var array
     */
    private $notFreePrice = [];

    /**
     * @var array
     */
    private $weight = [];

    /**
     * @var array
     */
    private $notFreeWeight = [];

    /**
     * @var array
     */
    private $qty = [];

    /**
     * @var array
     */
    private $notFreeQty = [];

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $validItems
     * @param string $hash
     * @param boolean $isFree
     *
     * @return Total
     */
    public function calculate($validItems, $hash, $isFree)
    {
        $this->setHash($hash);

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($validItems as $item) {
            if ($item->getParentItem() && $item->getParentItem()->getProductType() == ProductType::TYPE_BUNDLE
                || $item->getProduct()->isVirtual()
            ) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                $this->calculateByBundle($item);
            } else {
                $parentQty = 1;

                $this->calculateByItem($item, $parentQty);
                $notFreeQty = ($item->getQty() - $this->getFreeQty($item));

                $this->setWeight($this->getWeight() + $item->getWeight() * $item->getQty());
                $this->setNotFreeWeight(
                    $this->getNotFreeWeight() + $item->getWeight() * $notFreeQty
                );
            }
        }

        if ($isFree) {
            $this
                ->setNotFreeWeight(0)
                ->setNotFreePrice(0)
                ->setNotFreeQty(0);
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Quote\Model\Quote\Item\AbstractItem $item
     */
    protected function calculateByBundle($item)
    {
        $notFreeQty = ($item->getQty() - $this->getFreeQty($item));

        foreach ($item->getChildren() as $child) {
            if ($child->getProduct()->isVirtual()) {
                continue;
            }

            $childQty = $item->getQty() * $child->getQty();
            $notFreeChildQty = $item->getQty() * ($childQty - $this->getFreeQty($child));

            $this->calculateByItem($child, $item->getQty());

            if (!$item->getProduct()->getWeightType()) {
                $this->setWeight($this->getWeight() + $item->getWeight() * $childQty);
                $this->setNotFreeWeight(
                    $this->getNotFreeWeight() + $item->getWeight() * $notFreeChildQty
                );
            }
        }

        if ($item->getProduct()->getWeightType()) {
            $this->setWeight($this->getWeight() + $item->getWeight() * $item->getQty());
            $this->setNotFreeWeight(
                $this->getNotFreeWeight() + $item->getWeight() * $notFreeQty
            );
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param int $parentQty
     */
    protected function calculateByItem($item, $parentQty = 1)
    {
        $qty = $parentQty * $item->getQty();
        $notFreeQty = $qty - $parentQty * $this->getFreeQty($item);

        $this->setQty($this->getQty() + $qty);
        $this->setNotFreeQty($this->getNotFreeQty() + $notFreeQty);

        $this->setPrice($this->getPrice() + $item->getBasePrice() * $qty);
        $this->setNotFreePrice($this->getNotFreePrice() + $item->getBasePrice() * $notFreeQty);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Quote\Model\Quote\Item\AbstractItem $item
     *
     * @return int
     */
    private function getFreeQty($item)
    {
        $freeQty = 0;
        $freeShipping = $item->getFreeShipping();
        if ($freeShipping) {
            $freeQty = is_numeric($freeShipping) ? $freeShipping : $item->getQty();
        }

        return $freeQty;
    }

    /**
     * @param float $price
     *
     * @return Total
     */
    public function setPrice($price)
    {
        $this->price[$this->hash] = $price;

        return $this;
    }

    /**
     * @param float $notFreePrice
     *
     * @return Total
     */
    public function setNotFreePrice($notFreePrice)
    {
        $this->notFreePrice[$this->hash] = $notFreePrice;

        return $this;
    }

    /**
     * @param float $weight
     *
     * @return Total
     */
    public function setWeight($weight)
    {
        $this->weight[$this->hash] = $weight;

        return $this;
    }

    /**
     * @param float $notFreeWeight
     *
     * @return Total
     */
    public function setNotFreeWeight($notFreeWeight)
    {
        $this->notFreeWeight[$this->hash] = $notFreeWeight;

        return $this;
    }

    /**
     * @param float $qty
     *
     * @return Total
     */
    public function setQty($qty)
    {
        $this->qty[$this->hash] = $qty;

        return $this;
    }

    /**
     * @param float $notFreeQty
     *
     * @return Total
     */
    public function setNotFreeQty($notFreeQty)
    {
        $this->notFreeQty[$this->hash] = $notFreeQty;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return isset($this->price[$this->hash]) ? $this->price[$this->hash] : 0;
    }

    /**
     * @return float
     */
    public function getNotFreePrice()
    {
        return isset($this->notFreePrice[$this->hash]) ? $this->notFreePrice[$this->hash] : 0;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return isset($this->weight[$this->hash]) ? $this->weight[$this->hash] : 0;
    }

    /**
     * @return float
     */
    public function getNotFreeWeight()
    {
        return isset($this->notFreeWeight[$this->hash]) ? $this->notFreeWeight[$this->hash] : 0;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return isset($this->qty[$this->hash]) ? $this->qty[$this->hash] : 0;
    }

    /**
     * @return float
     */
    public function getNotFreeQty()
    {
        return isset($this->notFreeQty[$this->hash]) ? $this->notFreeQty[$this->hash] : 0;
    }

    /**
     * @param string $hash
     *
     * @return Total
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }
}
