<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionProductInterface;

interface SubscriptionProductRepositoryInterface
{

    /**
     * @param int $subscriptionProductId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionProductId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionProductInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionProductInterface
     */
    public function save(SubscriptionProductInterface $subscriptionProduct);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionProductInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionProductInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionProductInterface
     */
    public function delete(SubscriptionProductInterface $subscriptionProduct);

}