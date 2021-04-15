<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Magento\Framework\App\Helper\Context;

class DeviceHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $deviceManagerRepository;
    protected $attributeSetCollection;
    protected $accountManagement;
    protected $stripeCustomerCollectionFactory;

    public function __construct(
        Context $context,
        \Vonnda\DeviceManager\Api\DeviceManagerRepositoryInterface $deviceManagerRepository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\CollectionFactory $stripeCustomerCollectionFactory
    ) {
        parent::__construct($context);
        $this->deviceManagerRepository = $deviceManagerRepository;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->accountManagement = $accountManagement;
        $this->stripeCustomerCollectionFactory = $stripeCustomerCollectionFactory;
    }

    /**
     * If getById() can't load an object that has an id it throws an error, if it doesn't error, then it exists
     * @param int $id
     * @return Boolean
     */
    public function subscriptionDeviceExist(int $id)
    {
        try {
            $this->deviceManagerRepository->getById($id);
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /**
     * This is does basically the same checks as Vonnda\Checkout\Cron\ProcessOrder::execute() when creating customers and subscriptions.
     * In this function though, we only want to know if a subscription is going to be processed with the payment method set and the status set to autorenew_on.
     * This will be used in the order success email and when creating the success page.
     * These happen before the above cron fires which is why we cant just directly check the subscription.
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isSubActive($order)
    {
        if ($order->getPayment()->getMethod() == 'stripe_payments') { // all active scenarios require the stripe payment method
            $deviceSetId = $this->attributeSetCollection->create()->addFieldToSelect('*')->addFieldToFilter('attribute_set_name', 'Device')->getFirstItem()->getAttributeSetId();
            if ($this->orderHasDevice($order, $deviceSetId)) {
                if ($order->getCustomerId() == null) { // order placed by a guest
                    if ($this->accountManagement->doesMagentoCustomerExist(strtolower($order->getCustomerEmail())) === false) { // no existing customer, so one will be created
                        return true; // the stripe profile will be attached to the new customer
                    } 
                } else { // order placed by logged in customer
                    return true; // either they already had a stripe profile or they'll get one after checking out using the stripe method
                }
            }
        }
        return false;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $deviceSetId
     * @return bool
     */
    protected function orderHasDevice($order, $deviceSetId)
    {
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getAttributeSetId() == $deviceSetId) {
                return true;
            }
        }
        return false;
    }

}