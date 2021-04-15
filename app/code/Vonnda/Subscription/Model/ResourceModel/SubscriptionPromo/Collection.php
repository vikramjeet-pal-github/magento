<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Model\ResourceModel\SubscriptionPromo;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'id';
	protected $_eventPrefix = 'vonnda_subscription_subscriptionpromo_collection';
	protected $_eventObject = 'subscriptionpromo_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Vonnda\Subscription\Model\SubscriptionPromo', 'Vonnda\Subscription\Model\ResourceModel\SubscriptionPromo');
	}

}