<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Magento\Framework\Option\ArrayInterface;

class StatusSelect implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'active', 'label' => __('Active')],
            ['value' => 'inactive', 'label' => __('Inactive')]
        ];
    }
}