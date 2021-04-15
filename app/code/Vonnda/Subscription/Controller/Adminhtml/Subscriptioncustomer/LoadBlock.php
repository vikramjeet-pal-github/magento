<?php

namespace Vonnda\Subscription\Controller\Adminhtml\Subscriptioncustomer;

class LoadBlock extends \Magento\Sales\Controller\Adminhtml\Order\Create\LoadBlock
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Vonnda_Subscription::manage';
}
