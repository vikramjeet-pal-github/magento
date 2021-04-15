<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionPlan;

use Magento\Framework\Option\ArrayInterface;

class FrequencySelect implements ArrayInterface
{
    public function toOptionArray()
    {
        $limit = 12;
        $optionArray = [];
        for ($x = 0; $x <= ($limit - 1); $x++) {
            $optionArray[] = ['value' => $x+1, 'label' => __($x+1)];
        } 
        return $optionArray;
    }
}