<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Magento\Framework\Option\ArrayInterface;

class VisibleSelect implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Visible')],
            ['value' => 0, 'label' => __('Hidden')]
        ];
    }
}