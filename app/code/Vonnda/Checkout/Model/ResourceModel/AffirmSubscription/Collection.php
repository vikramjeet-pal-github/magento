<?php

namespace Vonnda\Checkout\Model\ResourceModel\AffirmSubscription;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'vonnda_checkout_affirm_subscriptions_collection';
    protected $_eventObject = 'affirm_subscriptions_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vonnda\Checkout\Model\AffirmSubscription', 'Vonnda\Checkout\Model\ResourceModel\AffirmSubscription');
    }
}