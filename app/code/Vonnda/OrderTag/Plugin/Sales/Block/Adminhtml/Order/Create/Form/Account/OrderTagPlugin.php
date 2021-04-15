<?php

namespace Vonnda\OrderTag\Plugin\Sales\Block\Adminhtml\Order\Create\Form\Account;

class OrderTagPlugin
{
    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\Create\Form\Account $subject,
        $result
    ) {
        $orderAttributesForm = $subject->getLayout()->createBlock(
            'Vonnda\OrderTag\Block\Adminhtml\Order\Create\OrderTagAttribute'
        );

        $orderAttributesForm->setTemplate('Vonnda_OrderTag::order/ordertag_attribute.phtml');
        $orderAttributesForm->setStore($subject->getStore());
        $orderAttributesFormHtml = $orderAttributesForm->toHtml();

        return $result . $orderAttributesFormHtml;
    }
}
