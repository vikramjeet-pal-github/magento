<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Observer;

use Magento\Customer\Observer\AfterAddressSaveObserver;

class AfterAddressSaveObserverOverride extends AfterAddressSaveObserver
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerAddress = $observer->getCustomerAddress();
        $customer = $customerAddress->getCustomer();

        if(!$customer){
            return;
        } else {
            parent::execute($observer);
        }
    }

}