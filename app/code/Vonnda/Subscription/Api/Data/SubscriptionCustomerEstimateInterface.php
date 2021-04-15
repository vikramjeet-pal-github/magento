<?php

namespace Vonnda\Subscription\Api\Data;

interface SubscriptionCustomerEstimateInterface
{
    /**
     * @return mixed
     */
    public function getSubtotal();

    /**
     * @param $subtotal
     * @return mixed
     */
    public function setSubtotal($subtotal);

    /**
     * @return mixed
     */
    public function getShipping();

    /**
     * @param $shipping
     * @return mixed
     */
    public function setShipping($shipping);

    /**
     * @return mixed
     */
    public function getTax();

    /**
     * @param $tax
     * @return mixed
     */
    public function setTax($tax);

    /**
     * @return mixed
     */
    public function getPromoCode();

    /**
     * @param $promoCode
     * @return mixed
     */
    public function setPromoCode($promoCode);

    /**
     * @return mixed
     */
    public function getOrderTotal();

    /**
     * @param $orderTotal
     * @return mixed
     */
    public function setOrderTotal($orderTotal);
}
