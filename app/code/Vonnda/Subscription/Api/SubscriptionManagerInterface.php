<?php
namespace Vonnda\Subscription\Api;

interface SubscriptionManagerInterface
{

    /**
     * Checks order for trigger product and creats subscrition if nessasary. 
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Customer\Model\Customer $customer
     * @param bool $addSubPayment
     * @return null
     */
    public function processOrder($order, $customer, $addSubPayment);

}