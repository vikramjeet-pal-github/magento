<?php

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateInterface;

class SubscriptionCustomerEstimate extends AbstractModel implements SubscriptionCustomerEstimateInterface
{
    const SUBTOTAL = 'subtotal';
    const SHIPPING = 'shipping';
    const TAX = 'tax';
    const PROMO_CODE = 'promo_code';
    const ORDER_TOTAL = 'order_total';

    public function getSubtotal()
    {
        return $this->_getData(self::SUBTOTAL);
    }

    public function setSubtotal($subtotal)
    {
        $this->setData(self::SUBTOTAL, $subtotal);
        return $this;
    }

    public function getShipping()
    {
        return $this->_getData(self::SHIPPING);
    }

    public function setShipping($shipping)
    {
        $this->setData(self::SHIPPING, $shipping);
        return $this;
    }

    public function getTax()
    {
        return $this->_getData(self::TAX);
    }

    public function setTax($tax)
    {
        $this->setData(self::TAX, $tax);
        return $this;
    }

    public function getPromoCode()
    {
        return $this->_getData(self::PROMO_CODE);
    }

    public function setPromoCode($promoCode)
    {
        $this->setData(self::PROMO_CODE, $promoCode);
        return $this;
    }

    public function getOrderTotal()
    {
        return $this->_getData(self::ORDER_TOTAL);
    }

    public function setOrderTotal($orderTotal)
    {
        $this->setData(self::ORDER_TOTAL, $orderTotal);
        return $this;
    }
}
