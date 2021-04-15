<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionOrderInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return SubscriptionOrderInterface
     */
    public function setId($id);
 
    /**
     * @return int $customerSubscriptionId
     */
    public function getCustomerSubscriptionId();
 
    /**
     * @param int $customerSubscriptionId
     * @return SubscriptionOrderInterface
     */
    public function setCustomerSubscriptionId($customerSubscriptionId);

    /**
     * @return int $orderId
     */
    public function getOrderId();
 
    /**
     * @param int $orderId
     * @return SubscriptionOrderInterface
     */
    public function setOrderId($orderId);

    /**
     * @return string
     */
    public function getStatus();
 
    /**
     * @param string $status
     * @return SubscriptionOrderInterface
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getErrorMessage();
 
    /**
     * @param string $errorMessage
     * @return SubscriptionOrderInterface
     */
    public function setErrorMessage($errorMessage);

    /**
     * @return string
     */
    public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return SubscriptionOrderInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();
 
    /**
     * @param string $updatedAt
     * @return SubscriptionOrderInterface
     */
    public function setUpdatedAt($updatedAt);
 
}