<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionCustomerInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);
 
    /**
     * @return int $customerId
     */
    public function getCustomerId();
 
    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return string
     */
    public function getStatus();
 
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getNextOrder();
 
    /**
     * @param string $nextOrder
     * @return $this
     */
    public function setNextOrder($nextOrder);

    /**
     * @return string
     */
    public function getLastOrder();
 
    /**
     * @param string $lastOrder
     * @return $this
     */
    public function setLastOrder($lastOrder);

    /**
     * @return string
     */
    public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();
 
    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string
     */
    public function getErrorMessage();
 
    /**
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage($errorMessage);

    /**
     * @return \Magento\Customer\Api\Data\AddressInterface|null $shippingAddress
     */
    public function getShippingAddress();
 
    /**
     * @param \Magento\Customer\Api\Data\AddressInterface|int|null $shippingAddress
     * @return $this
     */
    public function setShippingAddress($shippingAddress);

    /**
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface
     */
    public function getSubscriptionPlan();
 
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface|int $subscriptionPlan
     * @return $this
     */
    public function setSubscriptionPlan($subscriptionPlan);

    /**
     * @return \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface
     */
    public function getDevice();
 
    /**
     * @param \Vonnda\DeviceManager\Api\Data\DeviceManagerInterface|null $device
     * @return $this
     */
    public function setDevice($device);

    /**
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface|null
     */
    public function getPayment();

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface|null
     * @return $this
     */
    public function setPayment($subscriptionPayment);

 
    /**
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface[]
     */
    public function getPromos();

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface[]
     * @return $this
     */
    public function setPromos($promos);

     /**
     * @return string[]
     */
    public function getCouponCodes();

    /**
     * @param string[] $couponCodes
     * @return $this
     */
    public function setCouponCodes($couponCodes);

     /**
     * @return string
     */
    public function getCancelReason();
 
    /**
     * @param string $cancelReason
     * @return $this
     */
    public function setCancelReason($cancelReason);

    /**
     * @return string|null
     */
    public function getRenewalDate();

    /**
     * @param mixed $renewalDate
     * @return boolean
     */
    public function setRenewalDate($renewalDate);

    /**
     * @return string
     */
    public function getWeekOfString();

    /**
     * @param mixed $weekOfString
     * @return boolean
     */
    public function setWeekOfString($weekOfString);
 }