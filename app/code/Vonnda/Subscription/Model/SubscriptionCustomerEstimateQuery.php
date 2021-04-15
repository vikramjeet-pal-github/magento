<?php

namespace Vonnda\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterface;

class SubscriptionCustomerEstimateQuery extends AbstractModel implements SubscriptionCustomerEstimateQueryInterface
{
    const SUBSCRIPTION_ID = "subscription_id";

    const SHIPPING_ADDRESS_ID = "shipping_address_id";

    const COUPON_CODES = "coupon_codes";

    public function getSubscriptionId()
    {
        return $this->_getData(self::SUBSCRIPTION_ID);
    }

    public function setSubscriptionId($subscriptionId)
    {
        $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
        return $this;
    }

    public function getShippingAddressId()
    {
        return $this->_getData(self::SHIPPING_ADDRESS_ID);
    }

    public function setShippingAddressId($shippingAddressId)
    {
        $this->setData(self::SHIPPING_ADDRESS_ID, $shippingAddressId);
        return $this;
    }

    public function getCouponCodes()
    {
        return $this->_getData(self::COUPON_CODES);
    }

    public function setCouponCodes($couponCodes)
    {
        $this->setData(self::COUPON_CODES, $couponCodes);
        return $this;
    }
}
