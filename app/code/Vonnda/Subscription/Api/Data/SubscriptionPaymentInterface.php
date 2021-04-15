<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionPaymentInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return SubscriptionPaymentInterface
     */
    public function setId($id);
 
    /**
     * @return int $stripeCustomerId
     */
    public function getStripeCustomerId();
 
    /**
     * @param int $stripeCustomerId
     * @return SubscriptionPaymentInterface
     */
    public function setStripeCustomerId($stripeCustomerId);

    /**
     * @return int $paymentId
     */
    public function getPaymentId();
 
    /**
     * @param int $paymentId
     * @return SubscriptionPaymentInterface
     */
    public function setPaymentId($paymentId);

    /**
     * @return string $billingAddress
     */
    public function getBillingAddress();
 
    /**
     * @param string $billingAddress
     * @return SubscriptionPaymentInterface
     */
    public function setBillingAddress($billingAddress);

    /**
     * @return string
     */
    public function getStatus();
 
    /**
     * @param string $status
     * @return SubscriptionPaymentInterface
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getPaymentCode();
 
    /**
     * @param string $paymentCode
     * @return SubscriptionPaymentInterface
     */
    public function setPaymentCode($paymentCode);

    /**
     * @return string
     */
    public function getExpirationDate();
 
    /**
     * @param string $expirationDate
     * @return SubscriptionPaymentInterface
     */
    public function setExpirationDate($expirationDate);

    /**
     * @return string
     */
    public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return SubscriptionPaymentInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();
 
    /**
     * @param string $updatedAt
     * @return SubscriptionPaymentInterface
     */
    public function setUpdatedAt($updatedAt);
 
}