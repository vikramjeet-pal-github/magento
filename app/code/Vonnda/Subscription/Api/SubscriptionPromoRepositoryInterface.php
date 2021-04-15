<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPromoInterface;

interface SubscriptionPromoRepositoryInterface
{

    /**
     * @param int $subscriptionPromoId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionPromoId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface $subscriptionPromo
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface
     */
    public function save(SubscriptionPromoInterface $subscriptionPromo);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPromoInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface $subscriptionPromo
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPromoInterface
     */
    public function delete(SubscriptionPromoInterface $subscriptionPromo);

}