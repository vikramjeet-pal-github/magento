<?php

namespace Vonnda\OrderTag\Plugin\Sales\Model\AdminOrder\Create;

class CreateOrderPlugin
{
    public function afterCreateOrder(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        $result
    ) {

        if ($subject->hasData('order_tag_id')) {
            /** @var \Magento\Sales\Model\Order $result */
            $result->setOrderTagId((int) $subject->getData('order_tag_id'));
            $result->save();
        }

        return $result;
    }
}
