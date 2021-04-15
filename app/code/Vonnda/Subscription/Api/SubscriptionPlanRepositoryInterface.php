<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPlanInterface;

interface SubscriptionPlanRepositoryInterface
{

    /**
     * @param int $subscriptionPlanId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionPlanId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface
     */
    public function save(SubscriptionPlanInterface $subscriptionPlan);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param string|null $identifier
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface|null
     */
    public function getFirstByIdentifier($identifier);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface $subscriptionPlan
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface
     */
    public function delete(SubscriptionPlanInterface $subscriptionPlan);

}