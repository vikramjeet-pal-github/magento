<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionPromoInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return SubscriptionPromoInterface
     */
    public function setId($id);
 
    /**
     * @return int $subscriptionCustomerId
     */
    public function getSubscriptionCustomerId();
 
    /**
     * @param int $subscriptionCustomerId
     * @return SubscriptionPromoInterface
     */
    public function setSubscriptionCustomerId($subscriptionCustomerId);

    /**
     * @return int $subscriptionOrderId
     */
    public function getSubscriptionOrderId();
 
    /**
     * @param int $subscriptionOrderId
     * @return SubscriptionPromoInterface
     */
    public function setSubscriptionOrderId($subscriptionOrderId);

    /**
     * @return boolean
     */
    public function getUsedStatus();
 
    /**
     * @param boolean $usedStatus
     * @return SubscriptionPromoInterface
     */
    public function setUsedStatus($usedStatus);

    /**
     * @return string
     */
    public function getCouponCode();
 
    /**
     * @param string $couponCode
     * @return SubscriptionPromoInterface
     */
    public function setCouponCode($couponCode);

    /**
     * @return string
     */
    public function getErrorMessage();
 
    /**
     * @param string $errorMessage
     * @return SubscriptionPromoInterface
     */
    public function setErrorMessage($errorMessage);

    /**
     * @return string
     */
    public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return SubscriptionPromoInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUsedAt();
 
    /**
     * @param string $usedAt
     * @return SubscriptionPromoInterface
     */
    public function setUsedAt($usedAt);
 
}