<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface;

interface SubscriptionCustomerRepositoryInterface
{

    /**
     * @param int $subscriptionCustomerId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionCustomerId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface
     */
    public function save(SubscriptionCustomerInterface $subscriptionCustomer);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface
     */
    public function delete(SubscriptionCustomerInterface $subscriptionCustomer);

}