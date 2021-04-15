<?php
/**
 * @copyright: Copyright © 2020 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\GiftOrder\Plugin\Quote;

class LoadHandler
{
    // ADDS OUR gift_order AND gift_recipient_email TO THE QUOTE/QUOTE ADDRESS WHEN LOADED
    // /**
    //  * @param CartInterface $quote
    //  * @return CartInterface
    //  */
    // public function load(CartInterface $quote)
    // {
    //     if (!$quote->getIsActive()) {
    //         return $quote;
    //     }
    //     /** @var \Magento\Quote\Model\Quote $quote */
    //     $quote->setItems($quote->getAllVisibleItems());
    //     $shippingAssignments = [];
    //     if (!$quote->isVirtual() && $quote->getItemsQty() > 0) {
    //         $shippingAssignments[] = $this->shippingAssignmentProcessor->create($quote);
    //     }
    //     $cartExtension = $quote->getExtensionAttributes();
    //     if ($cartExtension === null) {
    //         $cartExtension = $this->cartExtensionFactory->create();
    //     }

    //     $cartExtension->setShippingAssignments($shippingAssignments);
    //     $quote->setExtensionAttributes($cartExtension);

    //     return $quote;
    // }
    public function afterLoad(
        \Magento\Quote\Model\QuoteRepository\LoadHandler $subject,
        $quote
    ) {
        $cartExtension = $quote->getExtensionAttributes();

        $shippingAddress = $quote->getShippingAddress();
        $shippingExtension = $shippingAddress->getExtensionAttributes();
        $shippingExtension->setGiftRecipientEmail($shippingAddress->getGiftRecipientEmail());

        $giftOrder = $quote->getGiftOrder();
        $cartExtension->setGiftOrder($giftOrder);
        $quote->setExtensionAttributes($cartExtension);

        return $quote;
    }
}