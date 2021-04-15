<?php

namespace Vonnda\Checkout\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class AffirmSubscription extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'vonnda_checkout_affirm_subscriptions';

    protected $_cacheTag = 'vonnda_checkout_affirm_subscriptions';

    protected $_eventPrefix = 'vonnda_checkout_affirm_subscriptions';

    protected function _construct()
    {
        $this->_init('Vonnda\Checkout\Model\ResourceModel\AffirmSubscription');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}