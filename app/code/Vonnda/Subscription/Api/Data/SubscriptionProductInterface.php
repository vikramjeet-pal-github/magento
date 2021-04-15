<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
interface SubscriptionProductInterface
{
    /**
     * @return int
     */
    public function getId();
 
    /**
     * @param int $id
     * @return SubscriptionProductInterface
     */
    public function setId($id);
 
    /**
     * @return int $subscriptionPlanId
     */
    public function getSubscriptionPlanId();
 
    /**
     * @param int $subscriptionPlanId
     * @return SubscriptionProductInterface
     */
    public function setSubscriptionPlanId($subscriptionPlanId);

    /**
     * @return int $productId
     */
    public function getProductId();
 
    /**
     * @param int $productId
     * @return SubscriptionProductInterface
     */
    public function setProductId($productId);

    /**
     * @return int $qty
     */
    public function getQty();
 
    /**
     * @param int $qty
     * @return SubscriptionProductInterface
     */
    public function setQty($qty);

    /**
     * @return float
     */
    public function getPriceOverride();
 
    /**
     * @param float $priceOverride
     * @return $this
     */
    public function setPriceOverride($priceOverride);
 
}