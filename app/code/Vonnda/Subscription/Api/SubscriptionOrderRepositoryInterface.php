<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionOrderInterface;

interface SubscriptionOrderRepositoryInterface
{

    /**
     * @param int $subscriptionOrderId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionOrderId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionOrderInterface $subscriptionOrder
     * @return \Vonnda\Subscription\Api\Data\SubscriptionOrderInterface
     */
    public function save(SubscriptionOrderInterface $subscriptionOrder);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionOrderInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionOrderInterface $subscriptionOrder
     * @return \Vonnda\Subscription\Api\Data\SubscriptionOrderInterface
     */
    public function delete(SubscriptionOrderInterface $subscriptionOrder);

}