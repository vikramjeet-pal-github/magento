<?php

namespace Vonnda\Subscription\Api\Data;

interface SubscriptionCustomerEstimateQueryInterface
{
    /**
     * @return mixed
     */
    public function getSubscriptionId();

    /**
     * @param $subscriptionId
     * @return mixed
     */
    public function setSubscriptionId($subscriptionId);

    /**
     * @return mixed
     */
    public function getShippingAddressId();

    /**
     * @param $shippingAddressId
     * @return mixed
     */
    public function setShippingAddressId($shippingAddressId);

    /**
     * @return mixed
     */
    public function getCouponCodes();

    /**
     * @param $couponCodes
     * @return mixed
     */
    public function setCouponCodes($couponCodes);

}
