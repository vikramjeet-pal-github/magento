<?php 

namespace Vonnda\Subscription\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SubscriptionServiceInterface
{
    /**
     * Gets all subscription plans
     *
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPlanInterface[]
     */
    public function getSubscriptionPlans();

    /**
     * Get current subscription customer and all related info
     *
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]
     */
    public function getSubscriptionCustomer();

    /**
     * Get subscription customer for current user by serial number
     * 
     * @param int $customerId
     * @param string $serialNumber
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface
     */
    public function getSubscriptionCustomerBySerialNumber(int $customerId, string $serialNumber);

    /**
     * Gets subscription customer and all related info by customer id
     *
     * @param int $customerId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]
     */
    public function getSubscriptionCustomerById(int $customerId);

    /**
     * Lists all subscription customers for given search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]
     */
    public function listSubscriptionCustomer(SearchCriteriaInterface $searchCriteria);

    /**
     * Creates a subscription customer and returns all related data
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]
     */
    public function createSubscriptionCustomer(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer);

    /**
     * Updates a subscription customer and returns all related data
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]

     */
    public function updateSubscriptionCustomer(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer);

    /**
     * Updates a subscription renewal date for customer and returns all related data
     *
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface[]

     */
    public function updateSubscriptionCustomerRenewal(\Vonnda\Subscription\Api\Data\SubscriptionCustomerInterface $subscriptionCustomer);

    /**
     * Fetch an accurate estimate of what the next shipment is going to cost
     *
     * 
     * @param \Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterface $subscriptionCustomerEstimateQuery
     * @return \Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateInterface
     */
    public function getSubscriptionCustomerEstimate(\Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterface $subscriptionCustomerEstimateQuery);
}
