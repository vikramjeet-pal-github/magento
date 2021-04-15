<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface;

interface SubscriptionHistoryRepositoryInterface
{

    /**
     * @param int $subscriptionProductId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionProductId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface
     */
    public function save(SubscriptionHistoryInterface $subscriptionProduct);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface
     */
    public function delete(SubscriptionHistoryInterface $subscriptionProduct);

}