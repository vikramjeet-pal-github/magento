<?php

namespace Vonnda\Subscription\Model\Source\SubscriptionCustomer;

use Vonnda\Subscription\Model\SubscriptionCustomer;
use Magento\Framework\Option\ArrayInterface;

class StatusSelect implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => SubscriptionCustomer::NEW_NO_PAYMENT_STATUS, 'label' => __('New No Payment')],
            ['value' => SubscriptionCustomer::LEGACY_NO_PAYMENT_STATUS, 'label' => __('Legacy No Payment')],
            ['value' => SubscriptionCustomer::PAYMENT_EXPIRED_STATUS, 'label' => __('Payment Expired')],
            ['value' => SubscriptionCustomer::PAYMENT_INVALID_STATUS, 'label' => __('Payment Invalid')],
            ['value' => SubscriptionCustomer::ACTIVATE_ELIGIBLE_STATUS, 'label' => __('Activate Eligible')],
            ['value' => SubscriptionCustomer::PROCESSING_ERROR_STATUS, 'label' => __('Processing Error')],
            ['value' => SubscriptionCustomer::AUTORENEW_OFF_STATUS, 'label' => __('Auto-Renew Off')],
            ['value' => SubscriptionCustomer::AUTORENEW_COMPLETE_STATUS, 'label' => __('Auto-Renew Complete')],
            ['value' => SubscriptionCustomer::AUTORENEW_ON_STATUS, 'label' => __('Auto-Renew On')],
            ['value' => SubscriptionCustomer::AUTORENEW_FREE_STATUS, 'label' => __('Auto-Renew Free')],
            ['value' => SubscriptionCustomer::RETURNED_STATUS, 'label' => __('Returned')]
        ];
    }
}
