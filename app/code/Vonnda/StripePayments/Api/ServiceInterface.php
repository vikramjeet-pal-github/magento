<?php

namespace Vonnda\StripePayments\Api;

interface ServiceInterface
{
     /**
     * Returns Customer Cards
     *
     * @api
     * @param int $customerId
     * @return array $cardData
     */
    public function getCustomerCards($customerId);

    /**
     * Get Stripe Customer
     *
     * @api
     * @param int $customerId
     * @return \Vonnda\StripePayments\Api\Data\StripeCustomerInterface
     */
    public function getCustomer($customerId);
    
    /**
     * Updates Stripe Customer
     *
     * @api
     * @param \Vonnda\StripePayments\Api\Data\StripeCustomerInterface $stripeCustomer
     * @return \Vonnda\StripePayments\Api\Data\StripeCustomerInterface
     */
    public function updateCustomer(\Vonnda\StripePayments\Api\Data\StripeCustomerInterface $stripeCustomer);
    
    /**
     * Returns all payment options
     *
     * @api
     * @param int $customerId
     * @return \Vonnda\Subscription\Api\Data\SubscriptionPaymentInterface[]
     */
    public function getPaymentOptions($customerId);

}
