<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionHistoryInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return SubscriptionHistoryInterface
     */
    public function setId($id);
 
    /**
     * @return int $subscriptionCustomerId
     */
    public function getSubscriptionCustomerId();
 
    /**
     * @param int $subscriptionCustomerId
     * @return SubscriptionHistoryInterface
     */
    public function setSubscriptionCustomerId($subscriptionCustomerId);

    /**
     * @return int $customerId
     */
    public function getCustomerId();
 
    /**
     * @param int $customerId
     * @return SubscriptionHistoryInterface
     */
    public function setCustomerId($customerId);

    /**
     * @return int $adminUserId
     */
    public function getAdminUserId();
 
    /**
     * @param int $adminUserId
     * @return SubscriptionHistoryInterface
     */
    public function setAdminUserId($adminUserId);

    /**
     * @return string $beforeSave
     */
    public function getBeforeSave();
 
    /**
     * @param string $beforeSave
     * @return SubscriptionHistoryInterface
     */
    public function setBeforeSave($beforeSave);

    /**
     * @return string $afterSave
     */
    public function getAfterSave();
 
    /**
     * @param string $afterSave
     * @return SubscriptionHistoryInterface
     */
    public function setAfterSave($afterSave);

    /**
     * @return string
     */
    public function getCreatedAt();
 
    /**
     * @param string $createdAt
     * @return SubscriptionCustomerInterface
     */
    public function setCreatedAt($createdAt);
 
}