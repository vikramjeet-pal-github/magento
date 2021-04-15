<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Magento\Framework\Option\ArrayInterface;

class StateSelect implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => SubscriptionCustomer::ACTIVE_STATE, 'label' => __('Active')],
            ['value' => SubscriptionCustomer::INACTIVE_STATE, 'label' => __('Inactive')],
            ['value' => SubscriptionCustomer::ERROR_STATE, 'label' => __('Error')]
        ];
    }
}
