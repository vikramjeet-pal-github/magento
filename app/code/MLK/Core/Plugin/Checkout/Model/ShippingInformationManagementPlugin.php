<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Plugin\Checkout\Model;


class ShippingInformationManagementPlugin
{
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ){
        $address = $addressInformation->getShippingAddress();
        $billingAddress = $addressInformation->getBillingAddress();

        $address->setSaveInAddressBook(1);
        $billingAddress->setSaveInAddressBook(1);

        return [$cartId, $addressInformation];
    }
}