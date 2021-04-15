<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface;

interface SubscriptionPaymentRepositoryInterface
{

    /**
     * @param int $subscriptionProductId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($subscriptionProductId);
    
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface
     */
    public function save(SubscriptionPaymentInterface $subscriptionProduct);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterfaceSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface $subscriptionProduct
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface
     */
    public function delete(SubscriptionPaymentInterface $subscriptionProduct);

}