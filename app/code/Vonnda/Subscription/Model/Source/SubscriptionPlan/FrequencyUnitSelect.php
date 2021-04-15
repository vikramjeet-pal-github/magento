<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Magento\Framework\Option\ArrayInterface;

class FrequencyUnitSelect implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'day', 'label' => __('Day(s)')],
            ['value' => 'week', 'label' => __('Week(s)')],
            ['value' => 'month', 'label' => __('Month(s)')],
            ['value' => 'year', 'label' => __('Year(s)')]
        ];
    }
}